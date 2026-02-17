<?php

declare(strict_types=1);

/**
 * Application Bootstrap
 *
 * Initializes the application environment, autoloading, and core services.
 */

// Ensure we're running from the project root
define('ROOT_PATH', __DIR__);
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Load Composer autoloader
require ROOT_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(ROOT_PATH);
$dotenv->safeLoad();

// Load configuration
$config = require CONFIG_PATH . '/config.php';

// Set error reporting based on environment
if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

// Set timezone
date_default_timezone_set('UTC');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $config['session']['lifetime'],
        'path' => $config['session']['cookie_path'],
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_name($config['session']['cookie_name']);
    session_start();
}

// Initialize the application container
$container = new \App\Container($config);

return $container;
