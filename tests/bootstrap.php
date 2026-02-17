<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File
 *
 * Sets up the testing environment by loading the Composer autoloader
 * and configuring any test-specific settings.
 */

// Load Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Set testing environment
$_ENV['APP_ENV'] = 'testing';

// Configure error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define test constants
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

// Load helper functions
require_once dirname(__DIR__) . '/src/Helper/functions.php';
