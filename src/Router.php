<?php

declare(strict_types=1);

namespace App;

use App\Exception\HttpException;

/**
 * Simple Router
 *
 * Maps URLs to controller actions.
 */
class Router
{
    private array $routes = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a GET route
     */
    public function get(string $path, string $controller, string $action): self
    {
        return $this->addRoute('GET', $path, $controller, $action);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, string $controller, string $action): self
    {
        return $this->addRoute('POST', $path, $controller, $action);
    }

    /**
     * Register a route for any method
     */
    public function any(string $path, string $controller, string $action): self
    {
        $this->addRoute('GET', $path, $controller, $action);
        $this->addRoute('POST', $path, $controller, $action);
        return $this;
    }

    /**
     * Add a route
     */
    private function addRoute(string $method, string $path, string $controller, string $action): self
    {
        // Convert path parameters like {id} to regex
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'path' => $path,
            'controller' => $controller,
            'action' => $action,
        ];

        return $this;
    }

    /**
     * Dispatch the current request
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove trailing slash except for root
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                // Extract named parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                $this->callAction($route['controller'], $route['action'], $params);
                return;
            }
        }

        // No route found - 404
        $this->notFound();
    }

    /**
     * Call a controller action
     */
    private function callAction(string $controllerClass, string $action, array $params): void
    {
        $fullClass = "App\\Controller\\{$controllerClass}";

        if (!class_exists($fullClass)) {
            throw new \RuntimeException("Controller not found: {$fullClass}");
        }

        $controller = new $fullClass($this->container);

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Action not found: {$controllerClass}::{$action}");
        }

        try {
            // Call the action with parameters
            $response = $controller->$action(...array_values($params));

            // If the action returns a string, output it
            if (is_string($response)) {
                echo $response;
            }
        } catch (HttpException $e) {
            $this->handleHttpException($e);
        }
    }

    /**
     * Handle HTTP exceptions
     */
    private function handleHttpException(HttpException $e): void
    {
        http_response_code($e->getStatusCode());

        $template = match ($e->getStatusCode()) {
            403 => 'errors/403',
            404 => 'errors/404',
            default => 'errors/500',
        };

        echo $this->container->template()->render($template, [
            'message' => $e->getMessage(),
        ]);
    }

    /**
     * Handle 404 Not Found
     */
    private function notFound(): void
    {
        http_response_code(404);
        echo $this->container->template()->render('errors/404');
    }
}
