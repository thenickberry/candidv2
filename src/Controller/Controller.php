<?php

declare(strict_types=1);

namespace App\Controller;

use App\Container;
use App\Exception\ForbiddenException;
use App\Service\Auth;
use App\Service\CategoryService;
use App\Service\Database;
use App\Service\Template;

/**
 * Base Controller
 *
 * Provides common functionality for all controllers.
 */
abstract class Controller
{
    protected Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Get the database service
     */
    protected function db(): Database
    {
        return $this->container->database();
    }

    /**
     * Get the auth service
     */
    protected function auth(): Auth
    {
        return $this->container->auth();
    }

    /**
     * Get the template service
     */
    protected function template(): Template
    {
        return $this->container->template();
    }

    /**
     * Get the category service
     */
    protected function categoryService(): CategoryService
    {
        return $this->container->categoryService();
    }

    /**
     * Get a configuration value
     */
    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->container->config($key, $default);
    }

    /**
     * Get current user
     */
    protected function user(): ?array
    {
        return $this->container->user();
    }

    /**
     * Render a view
     */
    protected function view(string $template, array $data = []): string
    {
        return $this->template()->renderWithLayout($template, 'layouts/main', $data);
    }

    /**
     * Render JSON response
     */
    protected function json(mixed $data, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json');
        return json_encode($data);
    }

    /**
     * Redirect to a URL
     */
    protected function redirect(string $url, int $code = 302): never
    {
        redirect($url, $code);
    }

    /**
     * Redirect back to previous page
     */
    protected function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        redirect($referer);
    }

    /**
     * Get a GET parameter
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get a POST parameter
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get all POST data
     */
    protected function allInput(): array
    {
        return $_POST;
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): void
    {
        $token = $this->input('csrf_token');
        if (!verify_csrf($token)) {
            throw new ForbiddenException('Invalid CSRF token');
        }
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        $this->auth()->requireAuth();

        // Check for forced password change (except on password change page)
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if ($this->auth()->mustChangePassword() && $path !== '/profile/password') {
            $this->flash('warning', 'You must change your password before continuing.');
            $this->redirect('/profile/password');
        }
    }

    /**
     * Require admin access
     */
    protected function requireAdmin(): void
    {
        $this->auth()->requireAdmin();
    }

    /**
     * Check if request is POST
     */
    protected function isPost(): bool
    {
        return is_post();
    }

    /**
     * Set a flash message
     *
     * @param string $key Message type (success, error, etc.)
     * @param string $message The message text
     * @param array|null $details Optional array of detail items for expandable section
     */
    protected function flash(string $key, string $message, ?array $details = null): void
    {
        flash($key, $message, $details);
    }
}
