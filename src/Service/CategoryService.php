<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Category Service
 *
 * Handles category management operations including soft-delete functionality.
 */
class CategoryService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get a category by ID (excludes soft-deleted)
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM category WHERE id = :id AND deleted_at IS NULL",
            ['id' => $id]
        );
    }

    /**
     * Get a category by ID including soft-deleted
     */
    public function findWithDeleted(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM category WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Get root categories (no parent, excludes soft-deleted)
     */
    public function getRootCategories(): array
    {
        $categories = $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM image_category ic
                     JOIN image_info i ON ic.image_id = i.id
                     WHERE ic.category_id = c.id AND i.deleted_at IS NULL) as image_count
             FROM category c
             WHERE c.parent IS NULL AND c.deleted_at IS NULL
             ORDER BY c.name"
        );

        // Add recursive image count including subcategories
        foreach ($categories as &$category) {
            $category['total_image_count'] = $this->countImagesRecursive((int) $category['id']);
        }

        return $categories;
    }

    /**
     * Get child categories (excludes soft-deleted)
     */
    public function getChildren(int $parentId): array
    {
        $categories = $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM image_category ic
                     JOIN image_info i ON ic.image_id = i.id
                     WHERE ic.category_id = c.id AND i.deleted_at IS NULL) as image_count
             FROM category c
             WHERE c.parent = :parent_id AND c.deleted_at IS NULL
             ORDER BY c.name",
            ['parent_id' => $parentId]
        );

        // Add recursive image count including subcategories
        foreach ($categories as &$category) {
            $category['total_image_count'] = $this->countImagesRecursive((int) $category['id']);
        }

        return $categories;
    }

    /**
     * Count images in a category and all its descendants (excludes soft-deleted)
     */
    public function countImagesRecursive(int $categoryId): int
    {
        $ids = $this->getDescendantIds($categoryId);
        $ids[] = $categoryId;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        return (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT ic.image_id)
             FROM image_category ic
             JOIN image_info i ON ic.image_id = i.id
             WHERE ic.category_id IN ({$placeholders}) AND i.deleted_at IS NULL",
            $ids
        );
    }

    /**
     * Get all descendant category IDs (excludes soft-deleted)
     */
    public function getDescendantIds(int $categoryId): array
    {
        $ids = [];
        $children = $this->db->fetchAll(
            "SELECT id FROM category WHERE parent = :parent_id AND deleted_at IS NULL",
            ['parent_id' => $categoryId]
        );

        foreach ($children as $child) {
            $childId = (int) $child['id'];
            $ids[] = $childId;
            $ids = array_merge($ids, $this->getDescendantIds($childId));
        }

        return $ids;
    }

    /**
     * Get all categories as a flat list with depth info (for dropdowns, excludes soft-deleted)
     */
    public function getFlatList(?int $parentId = null, int $depth = 0): array
    {
        $categories = [];

        $sql = "SELECT id, name FROM category WHERE deleted_at IS NULL AND parent " .
               ($parentId ? "= :parent" : "IS NULL") .
               " ORDER BY name";

        $rows = $this->db->fetchAll($sql, $parentId ? ['parent' => $parentId] : []);

        foreach ($rows as $row) {
            $row['depth'] = $depth;
            $row['indent'] = str_repeat('— ', $depth);
            $categories[] = $row;

            // Recursively get children
            $children = $this->getFlatList((int) $row['id'], $depth + 1);
            $categories = array_merge($categories, $children);
        }

        return $categories;
    }

    /**
     * Get breadcrumb path for a category
     */
    public function getBreadcrumb(array $category): array
    {
        $breadcrumb = [];
        $current = $category;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current['id'],
                'name' => $current['name'],
            ]);

            if ($current['parent']) {
                $current = $this->db->fetchOne(
                    "SELECT id, name, parent FROM category WHERE id = :id",
                    ['id' => $current['parent']]
                );
            } else {
                $current = null;
            }
        }

        return $breadcrumb;
    }

    /**
     * Create a new category
     */
    public function create(array $data): int
    {
        $id = $this->db->insert('category', [
            'name' => $data['name'],
            'descr' => $data['descr'] ?? null,
            'parent' => $data['parent'] ?: null,
            'owner' => $data['owner'],
            'public' => $data['public'] ?? 'y',
            'added' => date('Y-m-d H:i:s'),
        ]);

        // Update parent's haskids flag
        if (!empty($data['parent'])) {
            $this->db->update('category', ['haskids' => 1], 'id = :id', ['id' => $data['parent']]);
        }

        return $id;
    }

    /**
     * Update a category
     */
    public function update(int $id, array $data): bool
    {
        $updateData = [
            'name' => $data['name'],
            'descr' => $data['descr'] ?? null,
            'modified' => date('Y-m-d H:i:s'),
        ];

        if (isset($data['public'])) {
            $updateData['public'] = $data['public'];
        }

        if (isset($data['sort_by'])) {
            $updateData['sort_by'] = $data['sort_by'];
        }

        return $this->db->update('category', $updateData, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Hard delete a category (permanent, used for legacy compatibility)
     */
    public function delete(int $id): bool
    {
        // Check for children
        $hasChildren = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM category WHERE parent = :id AND deleted_at IS NULL",
            ['id' => $id]
        );

        if ($hasChildren > 0) {
            return false; // Cannot delete category with children
        }

        return $this->db->delete('category', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Soft-delete a category and all its subcategories
     * Also soft-deletes images that are ONLY in the deleted category tree
     */
    public function softDelete(int $id, int $deletedBy): bool
    {
        $now = date('Y-m-d H:i:s');

        // Get all descendant category IDs (including the category itself)
        $categoryIds = $this->getDescendantIds($id);
        $categoryIds[] = $id;

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Soft-delete all categories in the tree
            foreach ($categoryIds as $catId) {
                $this->db->update(
                    'category',
                    [
                        'deleted_at' => $now,
                        'deleted_by' => $deletedBy,
                    ],
                    'id = :id AND deleted_at IS NULL',
                    ['id' => $catId]
                );
            }

            // Find images that are ONLY in deleted categories (no other active category links)
            $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
            $imagesToDelete = $this->db->fetchAll(
                "SELECT DISTINCT ic.image_id
                 FROM image_category ic
                 JOIN image_info i ON ic.image_id = i.id
                 WHERE ic.category_id IN ({$placeholders})
                   AND i.deleted_at IS NULL
                   AND NOT EXISTS (
                       SELECT 1 FROM image_category ic2
                       JOIN category c ON ic2.category_id = c.id
                       WHERE ic2.image_id = ic.image_id
                         AND ic2.category_id NOT IN ({$placeholders})
                         AND c.deleted_at IS NULL
                   )",
                array_merge($categoryIds, $categoryIds)
            );

            // Soft-delete those images
            foreach ($imagesToDelete as $img) {
                $this->db->update(
                    'image_info',
                    [
                        'deleted_at' => $now,
                        'deleted_by' => $deletedBy,
                    ],
                    'id = :id AND deleted_at IS NULL',
                    ['id' => $img['image_id']]
                );
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Restore a soft-deleted category (and its subcategories)
     */
    public function restore(int $id): bool
    {
        $category = $this->findWithDeleted($id);
        if (!$category || $category['deleted_at'] === null) {
            return false;
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Restore the category
            $this->db->update(
                'category',
                [
                    'deleted_at' => null,
                    'deleted_by' => null,
                ],
                'id = :id',
                ['id' => $id]
            );

            // If parent is deleted, we need to move this to root
            if ($category['parent']) {
                $parent = $this->findWithDeleted((int) $category['parent']);
                if ($parent && $parent['deleted_at'] !== null) {
                    $this->db->update(
                        'category',
                        ['parent' => null],
                        'id = :id',
                        ['id' => $id]
                    );
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Hard-delete a category (permanent)
     */
    public function hardDelete(int $id): bool
    {
        return $this->db->delete('category', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Get all soft-deleted categories
     */
    public function getDeleted(): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, u.fname, u.lname, u.username,
                    du.fname as deleted_by_fname, du.lname as deleted_by_lname
             FROM category c
             LEFT JOIN user u ON c.owner = u.id
             LEFT JOIN user du ON c.deleted_by = du.id
             WHERE c.deleted_at IS NOT NULL
             ORDER BY c.deleted_at DESC"
        );
    }

    /**
     * Count soft-deleted categories
     */
    public function countDeleted(): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM category WHERE deleted_at IS NOT NULL"
        );
    }

    /**
     * Check if user can edit category
     */
    public function canEdit(int $categoryId, ?array $user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can edit any
        if (($user['access'] ?? 0) >= 5) {
            return true;
        }

        // Owner can edit
        $category = $this->find($categoryId);
        return $category && (int) $category['owner'] === (int) $user['id'];
    }

    /**
     * Get stats for what would be deleted (for confirmation dialog)
     */
    public function getDeletionStats(int $categoryId): array
    {
        $descendantIds = $this->getDescendantIds($categoryId);
        $allCategoryIds = array_merge([$categoryId], $descendantIds);

        $subcategoryCount = count($descendantIds);

        // Count images that are ONLY in the categories being deleted
        $placeholders = implode(',', array_fill(0, count($allCategoryIds), '?'));
        $imageCount = (int) $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT ic.image_id)
             FROM image_category ic
             JOIN image_info i ON ic.image_id = i.id
             WHERE ic.category_id IN ({$placeholders})
               AND i.deleted_at IS NULL
               AND NOT EXISTS (
                   SELECT 1 FROM image_category ic2
                   JOIN category c ON ic2.category_id = c.id
                   WHERE ic2.image_id = ic.image_id
                     AND ic2.category_id NOT IN ({$placeholders})
                     AND c.deleted_at IS NULL
               )",
            array_merge($allCategoryIds, $allCategoryIds)
        );

        return [
            'subcategories' => $subcategoryCount,
            'images' => $imageCount,
        ];
    }
}
