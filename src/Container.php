<?php

declare(strict_types=1);

namespace App;

use App\Service\Auth;
use App\Service\CategoryService;
use App\Service\Database;
use App\Service\ImageService;
use App\Service\ImageStorage;
use App\Service\Template;

/**
 * Simple Dependency Injection Container
 */
class Container
{
    private array $config;
    private array $instances = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }

        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function database(): Database
    {
        if (!isset($this->instances['database'])) {
            $this->instances['database'] = new Database(
                $this->config('database.host'),
                $this->config('database.name'),
                $this->config('database.user'),
                $this->config('database.pass'),
                $this->config('database.charset', 'utf8mb4')
            );
        }
        return $this->instances['database'];
    }

    public function auth(): Auth
    {
        if (!isset($this->instances['auth'])) {
            $this->instances['auth'] = new Auth(
                $this->database(),
                $this->config('session.cookie_name'),
                $this->config('session.lifetime')
            );
        }
        return $this->instances['auth'];
    }

    public function template(): Template
    {
        if (!isset($this->instances['template'])) {
            $this->instances['template'] = new Template(
                $this->config('paths.templates'),
                $this
            );
        }
        return $this->instances['template'];
    }

    public function imageStorage(): ImageStorage
    {
        if (!isset($this->instances['imageStorage'])) {
            $this->instances['imageStorage'] = new ImageStorage(
                $this->config('paths.images')
            );
        }
        return $this->instances['imageStorage'];
    }

    public function categoryService(): CategoryService
    {
        if (!isset($this->instances['categoryService'])) {
            $this->instances['categoryService'] = new CategoryService(
                $this->database()
            );
        }
        return $this->instances['categoryService'];
    }

    public function imageService(): ImageService
    {
        if (!isset($this->instances['imageService'])) {
            $this->instances['imageService'] = new ImageService(
                $this->database(),
                $this->imageStorage()
            );
        }
        return $this->instances['imageService'];
    }

    /**
     * Get the current authenticated user info
     */
    public function user(): ?array
    {
        return $this->auth()->getUser();
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return $this->auth()->check();
    }

    /**
     * Check if user has admin access
     */
    public function isAdmin(): bool
    {
        $user = $this->user();
        return $user && ($user['access'] ?? 0) >= 5;
    }
}
