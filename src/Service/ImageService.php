<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Image Service
 *
 * Handles image management operations including soft-delete functionality.
 */
class ImageService
{
    private Database $db;
    private ImageStorage $storage;

    public function __construct(Database $db, ImageStorage $storage)
    {
        $this->db = $db;
        $this->storage = $storage;
    }

    /**
     * Get an image by ID (excludes soft-deleted)
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
            ['id' => $id]
        );
    }

    /**
     * Get an image by ID including soft-deleted
     */
    public function findWithDeleted(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM image_info WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Soft-delete an image
     */
    public function softDelete(int $id, int $deletedBy): bool
    {
        return $this->db->update(
            'image_info',
            [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $deletedBy,
            ],
            'id = :id AND deleted_at IS NULL',
            ['id' => $id]
        ) > 0;
    }

    /**
     * Restore a soft-deleted image
     */
    public function restore(int $id): bool
    {
        return $this->db->update(
            'image_info',
            [
                'deleted_at' => null,
                'deleted_by' => null,
            ],
            'id = :id AND deleted_at IS NOT NULL',
            ['id' => $id]
        ) > 0;
    }

    /**
     * Hard-delete an image (permanent deletion including files)
     */
    public function hardDelete(int $id): bool
    {
        $image = $this->findWithDeleted($id);

        if (!$image) {
            return false;
        }

        // Only delete files if no other images reference them
        if (!empty($image['file_path'])) {
            $otherRefs = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM image_info WHERE file_path = :path AND id != :id",
                ['path' => $image['file_path'], 'id' => $id]
            );
            if ($otherRefs === 0) {
                $this->storage->delete($image['file_path']);
            }
        }
        if (!empty($image['thumb_path'])) {
            $otherRefs = (int) $this->db->fetchColumn(
                "SELECT COUNT(*) FROM image_info WHERE thumb_path = :path AND id != :id",
                ['path' => $image['thumb_path'], 'id' => $id]
            );
            if ($otherRefs === 0) {
                $this->storage->delete($image['thumb_path']);
            }
        }

        // Delete from database (cascades to image_category, people, image_comment, image_file, image_thumb)
        return $this->db->delete('image_info', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Get all soft-deleted images
     */
    public function getDeleted(): array
    {
        return $this->db->fetchAll(
            "SELECT i.*, u.fname, u.lname, u.username,
                    du.fname as deleted_by_fname, du.lname as deleted_by_lname
             FROM image_info i
             LEFT JOIN user u ON i.owner = u.id
             LEFT JOIN user du ON i.deleted_by = du.id
             WHERE i.deleted_at IS NOT NULL
             ORDER BY i.deleted_at DESC"
        );
    }

    /**
     * Count soft-deleted images
     */
    public function countDeleted(): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM image_info WHERE deleted_at IS NOT NULL"
        );
    }

    /**
     * Check if user can delete/restore image
     */
    public function canManage(int $imageId, ?array $user): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can manage any
        if (($user['access'] ?? 0) >= 5) {
            return true;
        }

        // Owner can manage
        $image = $this->findWithDeleted($imageId);
        return $image && (int) $image['owner'] === (int) $user['id'];
    }

    /**
     * Restore all images that were soft-deleted when a category was deleted
     * Only restores images that are ONLY in the restored category tree
     */
    public function restoreForCategory(int $categoryId): int
    {
        // Get image IDs that are linked to this category and are soft-deleted
        $images = $this->db->fetchAll(
            "SELECT DISTINCT ic.image_id
             FROM image_category ic
             JOIN image_info i ON ic.image_id = i.id
             WHERE ic.category_id = :category_id AND i.deleted_at IS NOT NULL",
            ['category_id' => $categoryId]
        );

        $restored = 0;
        foreach ($images as $image) {
            if ($this->restore((int) $image['image_id'])) {
                $restored++;
            }
        }

        return $restored;
    }

    /**
     * Empty all trash (hard-delete all soft-deleted images)
     */
    public function emptyTrash(): int
    {
        $deleted = $this->getDeleted();
        $count = 0;

        foreach ($deleted as $image) {
            if ($this->hardDelete((int) $image['id'])) {
                $count++;
            }
        }

        return $count;
    }
}
