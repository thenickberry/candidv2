<?php

declare(strict_types=1);

/**
 * CANDIDv2 Front Controller
 *
 * All requests are routed through this single entry point.
 */

// Load bootstrap
$container = require dirname(__DIR__) . '/bootstrap.php';

// Create router
$router = new \App\Router($container);

// Define routes
$router
    // Home
    ->get('/', 'HomeController', 'index')

    // Authentication
    ->get('/login', 'AuthController', 'showLogin')
    ->post('/login', 'AuthController', 'login')
    ->any('/logout', 'AuthController', 'logout')
    ->get('/register', 'AuthController', 'showRegister')
    ->post('/register', 'AuthController', 'register')

    // Browse
    ->get('/browse', 'BrowseController', 'index')
    ->get('/browse/{id}', 'BrowseController', 'category')

    // Images (specific routes before parameterized)
    ->get('/image/add', 'ImageController', 'showAdd')
    ->post('/image/add', 'ImageController', 'add')
    ->get('/image/bulk/edit', 'ImageController', 'showBulkEdit')
    ->post('/image/bulk/edit', 'ImageController', 'bulkEdit')
    ->post('/image/bulk/edit-json', 'ImageController', 'bulkEditJson')
    ->post('/image/bulk/delete', 'ImageController', 'bulkDelete')
    ->post('/image/bulk/rotate', 'ImageController', 'bulkRotate')
    ->get('/image/{id}/edit', 'ImageController', 'showEdit')
    ->post('/image/{id}/edit', 'ImageController', 'edit')
    ->post('/image/{id}/delete', 'ImageController', 'delete')
    ->post('/image/{id}/rotate', 'ImageController', 'rotate')
    ->get('/image/{id}/json', 'ImageController', 'getJson')
    ->post('/image/{id}/edit-json', 'ImageController', 'editJson')
    ->get('/image/{id}/show', 'ImageController', 'show')
    ->get('/image/{id}', 'ImageController', 'detail')

    // Search
    ->get('/search', 'SearchController', 'index')
    ->get('/search/options-json', 'SearchController', 'optionsJson')
    ->get('/search/results', 'SearchController', 'results')

    // Categories
    ->get('/category/add', 'CategoryController', 'showAdd')
    ->post('/category/add', 'CategoryController', 'add')
    ->get('/category/list-json', 'CategoryController', 'listJson')
    ->post('/category/add-json', 'CategoryController', 'addJson')
    ->get('/category/{id}/edit', 'CategoryController', 'showEdit')
    ->post('/category/{id}/edit', 'CategoryController', 'edit')
    ->get('/category/{id}/json', 'CategoryController', 'getJson')
    ->post('/category/{id}/edit-json', 'CategoryController', 'editJson')
    ->get('/category/{id}/deletion-stats', 'CategoryController', 'deletionStats')
    ->post('/category/{id}/delete', 'CategoryController', 'delete')

    // Comments
    ->post('/comment/{imageId}/add', 'CommentController', 'add')
    ->post('/comment/{id}/delete', 'CommentController', 'delete')

    // Profiles (specific routes before parameterized)
    ->get('/profile/edit', 'ProfileController', 'showEdit')
    ->post('/profile/edit', 'ProfileController', 'edit')
    ->get('/profile/password', 'ProfileController', 'showChangePassword')
    ->post('/profile/password', 'ProfileController', 'changePassword')
    ->get('/profile/{id}', 'ProfileController', 'show')

    // Admin (specific routes before parameterized)
    ->get('/admin/users', 'AdminController', 'users')
    ->get('/admin/users/create', 'AdminController', 'showCreateUser')
    ->post('/admin/users/create', 'AdminController', 'createUser')
    ->get('/admin/users/{id}/edit', 'AdminController', 'showEditUser')
    ->post('/admin/users/{id}/edit', 'AdminController', 'editUser')
    ->post('/admin/users/{id}/delete', 'AdminController', 'deleteUser')

    // Admin Trash
    ->get('/admin/trash', 'AdminController', 'trash')
    ->post('/admin/trash/empty', 'AdminController', 'emptyTrash')
    ->post('/admin/trash/categories/{id}/restore', 'AdminController', 'restoreCategory')
    ->post('/admin/trash/categories/{id}/purge', 'AdminController', 'purgeCategory')
    ->post('/admin/trash/images/{id}/restore', 'AdminController', 'restoreImage')
    ->post('/admin/trash/images/{id}/purge', 'AdminController', 'purgeImage')
    ->post('/admin/trash/bulk', 'AdminController', 'bulkAction');

// Dispatch the request
$router->dispatch();
