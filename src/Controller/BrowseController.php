<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Browse Controller
 *
 * Handles category browsing and image listing.
 */
class BrowseController extends Controller
{
    public function index(): string
    {
        // Get root categories with recursive image counts
        $categories = $this->categoryService()->getRootCategories();

        return $this->view('browse/index', [
            'title' => 'Browse Categories',
            'categories' => $categories,
            'canAddCategory' => $this->auth()->check(),
        ]);
    }

    public function category(string $id): string
    {
        $categoryId = (int) $id;

        // Get category info (exclude soft-deleted)
        $category = $this->db()->fetchOne(
            "SELECT * FROM category WHERE id = :id AND deleted_at IS NULL",
            ['id' => $categoryId]
        );

        if (!$category) {
            http_response_code(404);
            return $this->view('errors/404');
        }

        // Get subcategories with recursive image counts
        $subcategories = $this->categoryService()->getChildren($categoryId);

        // Get images in this category
        $userAccess = $this->user()['access'] ?? 0;
        $userId = $this->user()['id'] ?? 0;

        // Handle sorting - use category's sort_by as default, fall back to date_taken
        $sortOptions = [
            'date_taken' => 'i.date_taken DESC, i.added DESC',
            'date_added' => 'i.added DESC',
            'description' => 'i.descr ASC',
        ];
        $defaultSort = $category['sort_by'] ?? 'date_taken';
        $currentSort = $this->query('sort', $defaultSort);
        if (!isset($sortOptions[$currentSort])) {
            $currentSort = 'date_taken';
        }
        $orderBy = $sortOptions[$currentSort];

        $images = $this->db()->fetchAll(
            "SELECT i.id, i.descr, i.date_taken, i.views, i.width, i.height,
                    i.owner, i.camera, u.fname, u.lname
             FROM image_info i
             JOIN image_category ic ON i.id = ic.image_id
             LEFT JOIN user u ON i.photographer = u.id
             WHERE ic.category_id = :category_id
               AND i.deleted_at IS NULL
               AND (i.private = 0 OR i.owner = :user_id)
               AND i.access <= :access
             ORDER BY {$orderBy}",
            [
                'category_id' => $categoryId,
                'user_id' => $userId,
                'access' => $userAccess,
            ]
        );

        // Build breadcrumb
        $breadcrumb = $this->buildBreadcrumb($category);

        // Check if user can edit this category
        $canEdit = false;
        if ($this->auth()->check()) {
            $user = $this->user();
            $canEdit = ($user['access'] ?? 0) >= 5 || (int)$category['owner'] === (int)$user['id'];
        }

        return $this->view('browse/category', [
            'title' => h($category['name']),
            'category' => $category,
            'subcategories' => $subcategories,
            'images' => $images,
            'breadcrumb' => $breadcrumb,
            'canEdit' => $canEdit,
            'canAddCategory' => $this->auth()->check(),
            'currentUserId' => $userId,
            'isAdmin' => $this->auth()->isAdmin(),
            'currentSort' => $currentSort,
        ]);
    }

    /**
     * Build breadcrumb path for a category
     */
    private function buildBreadcrumb(array $category): array
    {
        $breadcrumb = [];
        $current = $category;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current['id'],
                'name' => $current['name'],
            ]);

            if ($current['parent']) {
                $current = $this->db()->fetchOne(
                    "SELECT id, name, parent FROM category WHERE id = :id",
                    ['id' => $current['parent']]
                );
            } else {
                $current = null;
            }
        }

        return $breadcrumb;
    }
}
