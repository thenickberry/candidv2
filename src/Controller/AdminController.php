<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CategoryService;
use App\Service\ImageService;
use App\Service\UserService;

/**
 * Admin Controller
 *
 * Handles admin-only operations like user management and trash.
 */
class AdminController extends Controller
{
    /**
     * List all users
     */
    public function users(): string
    {
        $this->requireAdmin();

        $users = $this->getUserService()->getAll();

        return $this->view('admin/users', [
            'title' => 'User Management',
            'users' => $users,
        ]);
    }

    /**
     * Show create user form
     */
    public function showCreateUser(): string
    {
        $this->requireAdmin();

        return $this->view('admin/user-create', [
            'title' => 'Create User',
        ]);
    }

    /**
     * Create a new user
     */
    public function createUser(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');
        $email = trim($this->input('email', ''));
        $fname = trim($this->input('fname', ''));
        $lname = trim($this->input('lname', ''));
        $access = (int) $this->input('access', 1);
        $mustChangePassword = $this->input('must_change_password') ? 1 : 0;

        $errors = [];

        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif ($this->getUserService()->findByUsername($username)) {
            $errors[] = 'Username already exists.';
        }

        if (empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode(' ', $errors));
            $this->redirect('/admin/users/create');
        }

        $userId = $this->getUserService()->create([
            'username' => $username,
            'pword' => $this->auth()->hashPassword($password),
            'email' => $email,
            'fname' => $fname,
            'lname' => $lname,
            'access' => $access,
            'must_change_password' => $mustChangePassword,
        ]);

        $this->flash('success', "User '{$username}' created successfully.");
        $this->redirect('/admin/users');
    }

    /**
     * Show edit user form
     */
    public function showEditUser(string $id): string
    {
        $this->requireAdmin();

        $userId = (int) $id;
        $user = $this->getUserService()->find($userId);

        if (!$user) {
            http_response_code(404);
            return $this->view('errors/404');
        }

        return $this->view('admin/user-edit', [
            'title' => 'Edit User',
            'editUser' => $user,
        ]);
    }

    /**
     * Update a user
     */
    public function editUser(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $userId = (int) $id;
        $user = $this->getUserService()->find($userId);

        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('/admin/users');
        }

        $data = [
            'email' => trim($this->input('email', '')),
            'fname' => trim($this->input('fname', '')),
            'lname' => trim($this->input('lname', '')),
            'access' => (int) $this->input('access', 1),
            'must_change_password' => $this->input('must_change_password') ? 1 : 0,
        ];

        // Only update password if provided
        $newPassword = $this->input('password', '');
        if (!empty($newPassword)) {
            if (strlen($newPassword) < 6) {
                $this->flash('error', 'Password must be at least 6 characters.');
                $this->redirect('/admin/users/' . $userId . '/edit');
            }
            $data['pword'] = $this->auth()->hashPassword($newPassword);
        }

        $this->getUserService()->update($userId, $data);

        $this->flash('success', 'User updated successfully.');
        $this->redirect('/admin/users');
    }

    /**
     * Delete a user
     */
    public function deleteUser(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $userId = (int) $id;

        // Prevent self-deletion
        if ($userId === (int) $this->user()['id']) {
            $this->flash('error', 'You cannot delete your own account.');
            $this->redirect('/admin/users');
        }

        $user = $this->getUserService()->find($userId);
        if ($user) {
            $this->getUserService()->delete($userId);
            $this->flash('success', "User '{$user['username']}' deleted.");
        }

        $this->redirect('/admin/users');
    }

    /**
     * View trash (soft-deleted items)
     */
    public function trash(): string
    {
        $this->requireAdmin();

        $deletedCategories = $this->getCategoryService()->getDeleted();
        $deletedImages = $this->getImageService()->getDeleted();

        return $this->view('admin/trash', [
            'title' => 'Trash',
            'deletedCategories' => $deletedCategories,
            'deletedImages' => $deletedImages,
        ]);
    }

    /**
     * Empty all trash (hard-delete all soft-deleted items)
     */
    public function emptyTrash(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $categoriesDeleted = 0;
        $imagesDeleted = 0;

        // Delete images first (they might be linked to categories)
        $imagesDeleted = $this->getImageService()->emptyTrash();

        // Delete categories
        $deletedCategories = $this->getCategoryService()->getDeleted();
        foreach ($deletedCategories as $category) {
            if ($this->getCategoryService()->hardDelete((int) $category['id'])) {
                $categoriesDeleted++;
            }
        }

        $messages = [];
        if ($categoriesDeleted > 0) {
            $messages[] = "{$categoriesDeleted} " . ($categoriesDeleted === 1 ? 'category' : 'categories');
        }
        if ($imagesDeleted > 0) {
            $messages[] = "{$imagesDeleted} " . ($imagesDeleted === 1 ? 'image' : 'images');
        }

        if (empty($messages)) {
            $this->flash('success', 'Trash is already empty.');
        } else {
            $this->flash('success', 'Permanently deleted: ' . implode(', ', $messages) . '.');
        }

        $this->redirect('/admin/trash');
    }

    /**
     * Restore a soft-deleted category
     */
    public function restoreCategory(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $categoryId = (int) $id;

        if ($this->getCategoryService()->restore($categoryId)) {
            $this->flash('success', 'Category restored.');
        } else {
            $this->flash('error', 'Failed to restore category.');
        }

        $this->redirect('/admin/trash');
    }

    /**
     * Permanently delete a category
     */
    public function purgeCategory(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $categoryId = (int) $id;

        if ($this->getCategoryService()->hardDelete($categoryId)) {
            $this->flash('success', 'Category permanently deleted.');
        } else {
            $this->flash('error', 'Failed to delete category.');
        }

        $this->redirect('/admin/trash');
    }

    /**
     * Restore a soft-deleted image
     */
    public function restoreImage(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $imageId = (int) $id;

        if ($this->getImageService()->restore($imageId)) {
            $this->flash('success', 'Image restored.');
        } else {
            $this->flash('error', 'Failed to restore image.');
        }

        $this->redirect('/admin/trash');
    }

    /**
     * Permanently delete an image
     */
    public function purgeImage(string $id): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $imageId = (int) $id;

        if ($this->getImageService()->hardDelete($imageId)) {
            $this->flash('success', 'Image permanently deleted.');
        } else {
            $this->flash('error', 'Failed to delete image.');
        }

        $this->redirect('/admin/trash');
    }

    /**
     * Bulk action on trash items
     */
    public function bulkAction(): void
    {
        $this->requireAdmin();
        $this->validateCsrf();

        $action = $this->input('action', '');
        $categoryIds = $this->input('categories', []);
        $imageIds = $this->input('images', []);

        if (!is_array($categoryIds)) {
            $categoryIds = [];
        }
        if (!is_array($imageIds)) {
            $imageIds = [];
        }

        $restored = 0;
        $deleted = 0;

        if ($action === 'restore') {
            foreach ($categoryIds as $id) {
                if ($this->getCategoryService()->restore((int) $id)) {
                    $restored++;
                }
            }
            foreach ($imageIds as $id) {
                if ($this->getImageService()->restore((int) $id)) {
                    $restored++;
                }
            }
            if ($restored > 0) {
                $this->flash('success', "Restored {$restored} " . ($restored === 1 ? 'item' : 'items') . '.');
            } else {
                $this->flash('error', 'No items selected.');
            }
        } elseif ($action === 'purge') {
            // Delete images first
            foreach ($imageIds as $id) {
                if ($this->getImageService()->hardDelete((int) $id)) {
                    $deleted++;
                }
            }
            // Then categories
            foreach ($categoryIds as $id) {
                if ($this->getCategoryService()->hardDelete((int) $id)) {
                    $deleted++;
                }
            }
            if ($deleted > 0) {
                $this->flash('success', "Permanently deleted {$deleted} " . ($deleted === 1 ? 'item' : 'items') . '.');
            } else {
                $this->flash('error', 'No items selected.');
            }
        } else {
            $this->flash('error', 'Invalid action.');
        }

        $this->redirect('/admin/trash');
    }

    private function getUserService(): UserService
    {
        static $service = null;
        if ($service === null) {
            $service = new UserService($this->db());
        }
        return $service;
    }

    private function getCategoryService(): CategoryService
    {
        return $this->container->categoryService();
    }

    private function getImageService(): ImageService
    {
        return $this->container->imageService();
    }
}
