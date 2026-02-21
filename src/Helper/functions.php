<?php

declare(strict_types=1);

/**
 * Global Helper Functions
 */

if (!function_exists('h')) {
    /**
     * Escape HTML entities for safe output (XSS prevention)
     */
    function h(?string $string): string
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('e')) {
    /**
     * Alias for h() - escape HTML
     */
    function e(?string $string): string
    {
        return h($string);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the current CSRF token
     */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a hidden CSRF input field
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * Verify the CSRF token from a request
     */
    function verify_csrf(?string $token): bool
    {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('config')) {
    /**
     * Get a configuration value using dot notation
     */
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;

        if ($config === null) {
            $config = require dirname(__DIR__, 2) . '/config/config.php';
        }

        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}

if (!function_exists('app_url')) {
    /**
     * Generate a full URL for the application
     */
    function app_url(string $path = ''): string
    {
        $base = rtrim(config('app.url', ''), '/');
        $path = ltrim($path, '/');
        return $path ? "{$base}/{$path}" : $base;
    }
}

if (!function_exists('redirect')) {
    /**
     * Safe redirect - only allows redirects to same host
     */
    function redirect(string $url, int $code = 302): never
    {
        // If it's a relative URL, allow it
        if (!preg_match('#^https?://#i', $url)) {
            header("Location: {$url}", true, $code);
            exit;
        }

        // For absolute URLs, verify same host
        $parsed = parse_url($url);
        $appHost = parse_url(config('app.url', ''), PHP_URL_HOST);

        if (isset($parsed['host']) && $parsed['host'] !== $appHost) {
            // Redirect to home instead of external URL
            header("Location: /", true, $code);
            exit;
        }

        header("Location: {$url}", true, $code);
        exit;
    }
}

if (!function_exists('is_post')) {
    /**
     * Check if the current request is a POST request
     */
    function is_post(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}

if (!function_exists('is_ajax')) {
    /**
     * Check if the current request is an AJAX request
     */
    function is_ajax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

if (!function_exists('flash')) {
    /**
     * Set or get a flash message
     *
     * @param string|null $key Message type (success, error, etc.)
     * @param string|null $message The message text
     * @param array|null $details Optional array of detail items for expandable section
     */
    function flash(?string $key = null, ?string $message = null, ?array $details = null): mixed
    {
        if ($key === null) {
            // Get all flash messages and clear them
            $messages = $_SESSION['flash'] ?? [];
            unset($_SESSION['flash']);
            return $messages;
        }

        if ($message === null) {
            // Get specific flash message
            $value = $_SESSION['flash'][$key] ?? null;
            unset($_SESSION['flash'][$key]);
            return $value;
        }

        // Set flash message (with optional details)
        if ($details !== null && !empty($details)) {
            $_SESSION['flash'][$key] = [
                'message' => $message,
                'details' => $details,
            ];
        } else {
            $_SESSION['flash'][$key] = $message;
        }
        return null;
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value (for form repopulation after validation errors)
     */
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['old_input'][$key] ?? $default;
    }
}

if (!function_exists('format_date')) {
    /**
     * Format a date string
     */
    function format_date(?string $date, string $format = 'M j, Y'): string
    {
        if (empty($date)) {
            return '';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '';
    }
}

if (!function_exists('format_datetime')) {
    /**
     * Format a datetime string
     */
    function format_datetime(?string $datetime, string $format = 'M j, Y g:i A'): string
    {
        return format_date($datetime, $format);
    }
}
