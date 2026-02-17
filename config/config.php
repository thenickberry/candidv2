<?php

declare(strict_types=1);

/**
 * Application Configuration
 *
 * Loads configuration from environment variables with sensible defaults.
 */

return [
    // Application
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'version' => '3.0.0',
    ],

    // Database
    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'candid',
        'user' => $_ENV['DB_USER'] ?? 'candid',
        'pass' => $_ENV['DB_PASS'] ?? '',
        'charset' => 'utf8mb4',
    ],

    // Session
    'session' => [
        'cookie_name' => $_ENV['COOKIE_NAME'] ?? 'candid',
        'cookie_path' => $_ENV['COOKIE_PATH'] ?? '/',
        'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 86400),
    ],

    // Image defaults
    'images' => [
        'default_width' => (int) ($_ENV['DEFAULT_WIDTH'] ?? 480),
        'default_height' => (int) ($_ENV['DEFAULT_HEIGHT'] ?? 360),
        'thumb_width' => (int) ($_ENV['THUMB_WIDTH'] ?? 400),
        'thumb_height' => (int) ($_ENV['THUMB_HEIGHT'] ?? 400),
        'thumb_quality' => (int) ($_ENV['THUMB_QUALITY'] ?? 100),
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
        'max_size' => 50 * 1024 * 1024, // 50MB
    ],

    // Display defaults
    'display' => [
        'default_rows' => (int) ($_ENV['DEFAULT_ROWS'] ?? 5),
        'default_cols' => (int) ($_ENV['DEFAULT_COLS'] ?? 2),
    ],

    // Paths
    'paths' => [
        'uploads' => $_ENV['UPLOAD_DIR'] ?? dirname(__DIR__) . '/storage/uploads',
        'images' => dirname(__DIR__) . '/storage/images',
        'templates' => dirname(__DIR__) . '/templates',
        'cache' => dirname(__DIR__) . '/storage/cache',
    ],

    // Theme
    'theme' => [
        'default' => $_ENV['DEFAULT_THEME'] ?? 'default',
    ],
];
