<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Image Storage Service
 *
 * Handles filesystem-based image storage with hash-based sharding.
 * Images are stored in a directory structure based on their hash
 * to distribute files across directories and avoid filesystem limits.
 */
class ImageStorage
{
    private string $basePath;
    private int $shardDepth;

    public function __construct(string $basePath, int $shardDepth = 2)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->shardDepth = $shardDepth;

        if (!is_dir($this->basePath)) {
            mkdir($this->basePath, 0755, true);
        }
    }

    /**
     * Store image data and return the storage path
     */
    public function store(string $data, string $type = 'full'): string
    {
        $hash = hash('sha256', $data);
        $path = $this->hashToPath($hash, $type);
        $fullPath = $this->basePath . '/' . $path;

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fullPath, $data);

        return $path;
    }

    /**
     * Store image from a file path
     */
    public function storeFile(string $filePath, string $type = 'full'): string
    {
        $data = file_get_contents($filePath);
        return $this->store($data, $type);
    }

    /**
     * Retrieve image data by path
     */
    public function retrieve(string $path): ?string
    {
        $fullPath = $this->basePath . '/' . $path;

        if (!file_exists($fullPath)) {
            return null;
        }

        return file_get_contents($fullPath);
    }

    /**
     * Delete an image by path
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->basePath . '/' . $path;

        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Check if an image exists
     */
    public function exists(string $path): bool
    {
        return file_exists($this->basePath . '/' . $path);
    }

    /**
     * Get the full filesystem path for a storage path
     */
    public function getFullPath(string $path): string
    {
        return $this->basePath . '/' . $path;
    }

    /**
     * Convert a hash to a sharded path
     * Example with depth 2: "ab/cd/abcdef1234567890.jpg"
     */
    private function hashToPath(string $hash, string $type): string
    {
        $parts = [];

        for ($i = 0; $i < $this->shardDepth; $i++) {
            $parts[] = substr($hash, $i * 2, 2);
        }

        $extension = $type === 'thumb' ? '_thumb.jpg' : '.jpg';
        $parts[] = $hash . $extension;

        return implode('/', $parts);
    }

    /**
     * Get storage statistics
     */
    public function getStats(): array
    {
        $totalFiles = 0;
        $totalSize = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->basePath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalFiles++;
                $totalSize += $file->getSize();
            }
        }

        return [
            'files' => $totalFiles,
            'size' => $totalSize,
            'size_human' => $this->formatBytes($totalSize),
        ];
    }

    /**
     * Format bytes to human readable string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
