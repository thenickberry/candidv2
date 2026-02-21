<?php

declare(strict_types=1);

namespace App\Service;

use App\Container;

/**
 * Template Service
 *
 * Simple PHP template rendering with layout support.
 */
class Template
{
    private string $templatePath;
    private Container $container;
    private array $globalData = [];

    public function __construct(string $templatePath, Container $container)
    {
        $this->templatePath = rtrim($templatePath, '/');
        $this->container = $container;
    }

    /**
     * Set global data available to all templates
     */
    public function share(string $key, mixed $value): void
    {
        $this->globalData[$key] = $value;
    }

    /**
     * Render a template
     */
    public function render(string $template, array $data = []): string
    {
        $file = $this->templatePath . '/' . $template . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Template not found: {$template}");
        }

        // Merge global data with template data
        $data = array_merge($this->globalData, $data);

        // Add container services to template context
        $data['container'] = $this->container;
        $data['auth'] = $this->container->auth();
        $data['user'] = $this->container->user();
        $data['config'] = fn(string $key, $default = null) => $this->container->config($key, $default);

        // Extract data to local scope
        extract($data, EXTR_SKIP);

        // Capture output
        ob_start();
        try {
            include $file;
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Render a template with a layout
     */
    public function renderWithLayout(string $template, string $layout, array $data = []): string
    {
        // Render the content template first
        $content = $this->render($template, $data);

        // Then render the layout with content
        return $this->render($layout, array_merge($data, ['content' => $content]));
    }

    /**
     * Include a partial template
     */
    public function partial(string $template, array $data = []): string
    {
        return $this->render('partials/' . $template, $data);
    }

    /**
     * Escape HTML
     */
    public function escape(?string $string): string
    {
        return h($string);
    }
}
