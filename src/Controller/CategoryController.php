<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ForbiddenException;

/**
 * Category Controller
 */
class CategoryController extends Controller
{
    public function showAdd(): string
    {
        $this->requireAuth();

        $categories = $this->getCategoryService()->getFlatList();

        return $this->view('category/add', [
            'title' => 'Add Category',
            'categories' => $categories,
        ]);
    }

    public function add(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $name = trim($this->input('name', ''));

        if (empty($name)) {
            $this->flash('error', 'Category name is required.');
            $this->redirect('/category/add');
        }

        $id = $this->getCategoryService()->create([
            'name' => $name,
            'descr' => $this->input('descr', ''),
            'parent' => $this->input('parent_id') ?: null,
            'owner' => $this->user()['id'],
            'public' => $this->input('public', 'y'),
        ]);

        $this->getHistoryService()->log(
            'create',
            $this->user()['id'],
            "Created category: {$name}",
            'category',
            $id
        );

        $this->flash('success', 'Category created.');
        $this->redirect('/browse/' . $id);
    }

    /**
     * Get categories and users list as JSON for modals
     */
    public function listJson(): void
    {
        $this->requireAuth();

        header('Content-Type: application/json');

        $categories = $this->getCategoryService()->getFlatList();

        $users = $this->db()->fetchAll(
            "SELECT id, username, fname, lname FROM user WHERE onlist = 'y' ORDER BY lname, fname"
        );

        echo json_encode([
            'categories' => $categories,
            'users' => $users,
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Create category via AJAX (returns JSON)
     */
    public function addJson(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        header('Content-Type: application/json');

        $name = trim($this->input('name', ''));

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required.']);
            return;
        }

        $id = $this->getCategoryService()->create([
            'name' => $name,
            'descr' => $this->input('descr', ''),
            'parent' => $this->input('parent_id') ?: null,
            'owner' => $this->user()['id'],
            'public' => $this->input('public', 'y'),
        ]);

        $this->getHistoryService()->log(
            'create',
            $this->user()['id'],
            "Created category: {$name}",
            'category',
            $id
        );

        echo json_encode([
            'id' => $id,
            'name' => $name,
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }

    public function showEdit(string $id): string
    {
        $this->requireAuth();

        $categoryId = (int) $id;
        $category = $this->getCategoryService()->find($categoryId);

        if (!$category) {
            http_response_code(404);
            return $this->view('errors/404');
        }

        if (!$this->getCategoryService()->canEdit($categoryId, $this->user())) {
            http_response_code(403);
            return $this->view('errors/403');
        }

        $categories = $this->getCategoryService()->getFlatList();

        return $this->view('category/edit', [
            'title' => 'Edit Category',
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    /**
     * Get category data as JSON for modal editing
     */
    public function getJson(string $id): void
    {
        $this->requireAuth();

        $categoryId = (int) $id;
        $category = $this->getCategoryService()->find($categoryId);

        header('Content-Type: application/json');

        if (!$category) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        if (!$this->getCategoryService()->canEdit($categoryId, $this->user())) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        echo json_encode([
            'category' => [
                'id' => $category['id'],
                'name' => $category['name'],
                'descr' => $category['descr'] ?? '',
                'public' => $category['public'] ?? 'y',
                'sort_by' => $category['sort_by'] ?? '',
            ],
            'csrfToken' => csrf_token(),
        ]);
    }

    /**
     * Update category via AJAX (returns JSON)
     */
    public function editJson(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $categoryId = (int) $id;

        header('Content-Type: application/json');

        if (!$this->getCategoryService()->canEdit($categoryId, $this->user())) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $name = trim($this->input('name', ''));

        if (empty($name)) {
            http_response_code(400);
            echo json_encode(['error' => 'Category name is required.']);
            return;
        }

        $sortBy = $this->input('sort_by', '');
        $validSorts = ['date_taken', 'date_added', 'description', ''];
        if (!in_array($sortBy, $validSorts)) {
            $sortBy = '';
        }

        $this->getCategoryService()->update($categoryId, [
            'name' => $name,
            'descr' => $this->input('descr', ''),
            'public' => $this->input('public', 'y'),
            'sort_by' => $sortBy ?: null,
        ]);

        $this->getHistoryService()->log(
            'update',
            $this->user()['id'],
            "Updated category: {$name}",
            'category',
            $categoryId
        );

        echo json_encode([
            'success' => true,
            'category' => [
                'id' => $categoryId,
                'name' => $name,
                'descr' => $this->input('descr', ''),
                'public' => $this->input('public', 'y'),
                'sort_by' => $sortBy,
            ],
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $categoryId = (int) $id;

        if (!$this->getCategoryService()->canEdit($categoryId, $this->user())) {
            throw new ForbiddenException();
        }

        $name = trim($this->input('name', ''));

        if (empty($name)) {
            $this->flash('error', 'Category name is required.');
            $this->redirect('/category/' . $categoryId . '/edit');
        }

        $sortBy = $this->input('sort_by', '');
        $validSorts = ['date_taken', 'date_added', 'description', ''];
        if (!in_array($sortBy, $validSorts)) {
            $sortBy = '';
        }

        $this->getCategoryService()->update($categoryId, [
            'name' => $name,
            'descr' => $this->input('descr', ''),
            'public' => $this->input('public', 'y'),
            'sort_by' => $sortBy ?: null,
        ]);

        $this->getHistoryService()->log(
            'update',
            $this->user()['id'],
            "Updated category: {$name}",
            'category',
            $categoryId
        );

        $this->flash('success', 'Category updated.');
        $this->redirect('/browse/' . $categoryId);
    }

    /**
     * Get deletion stats (JSON endpoint for modal)
     */
    public function deletionStats(string $id): void
    {
        $this->requireAuth();

        $categoryId = (int) $id;

        if (!$this->getCategoryService()->canEdit($categoryId, $this->user())) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $category = $this->getCategoryService()->find($categoryId);

        if (!$category) {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not found']);
            return;
        }

        $stats = $this->getCategoryService()->getDeletionStats($categoryId);

        header('Content-Type: application/json');
        echo json_encode([
            'name' => $category['name'],
            'subcategories' => $stats['subcategories'],
            'images' => $stats['images'],
        ]);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $categoryId = (int) $id;

        if (!$this->getCategoryService()->canEdit($categoryId, $this->user())) {
            throw new ForbiddenException();
        }

        $category = $this->getCategoryService()->find($categoryId);

        if (!$category) {
            $this->flash('error', 'Category not found.');
            $this->redirect('/browse');
        }

        if ($this->getCategoryService()->softDelete($categoryId, (int) $this->user()['id'])) {
            $this->getHistoryService()->log(
                'delete',
                $this->user()['id'],
                "Deleted category: {$category['name']}",
                'category',
                $categoryId
            );

            $this->flash('success', 'Category moved to trash.');
            $this->redirect('/browse');
        } else {
            $this->flash('error', 'Failed to delete category.');
            $this->redirect('/browse/' . $categoryId);
        }
    }

    private function getCategoryService(): \App\Service\CategoryService
    {
        static $service = null;
        if ($service === null) {
            $service = new \App\Service\CategoryService($this->db());
        }
        return $service;
    }

    private function getHistoryService(): \App\Service\HistoryService
    {
        static $service = null;
        if ($service === null) {
            $service = new \App\Service\HistoryService($this->db());
        }
        return $service;
    }
}
