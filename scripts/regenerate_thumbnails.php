<?php

/**
 * Regenerate thumbnails for all images using Intervention Image
 *
 * Usage: php scripts/regenerate_thumbnails.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;

$db = $container->database();
$storage = $container->imageStorage();
$manager = new ImageManager(new Driver());

// Get thumbnail settings from config
$thumbWidth = $container->config('images.thumb_width', 400);
$thumbHeight = $container->config('images.thumb_height', 400);
$thumbQuality = $container->config('images.thumb_quality', 100);

echo "Regenerating thumbnails ({$thumbWidth}x{$thumbHeight} at {$thumbQuality}% quality)...\n\n";

// Get all images with file paths
$images = $db->fetchAll(
    "SELECT id, file_path, thumb_path FROM image_info WHERE file_path IS NOT NULL"
);

$total = count($images);
$success = 0;
$failed = 0;

echo "Found {$total} images to process.\n\n";

foreach ($images as $index => $image) {
    $num = $index + 1;
    echo "[{$num}/{$total}] Image #{$image['id']}: ";

    try {
        // Get the full image path
        $fullPath = $storage->getFullPath($image['file_path']);

        if (!file_exists($fullPath)) {
            echo "SKIP (file not found)\n";
            $failed++;
            continue;
        }

        // Read and create thumbnail with Intervention Image
        $img = $manager->read($fullPath);

        $thumbData = $img
            ->scaleDown($thumbWidth, $thumbHeight)
            ->toJpeg($thumbQuality)
            ->toString();

        // Delete old thumbnail if exists
        if (!empty($image['thumb_path'])) {
            $storage->delete($image['thumb_path']);
        }

        // Store new thumbnail
        $thumbPath = $storage->store($thumbData, 'thumb');

        // Update database
        $db->update('image_info', ['thumb_path' => $thumbPath], 'id = :id', ['id' => $image['id']]);

        echo "OK\n";
        $success++;

    } catch (Exception $e) {
        echo "ERROR ({$e->getMessage()})\n";
        $failed++;
    }
}

echo "\n";
echo "Done!\n";
echo "  Success: {$success}\n";
echo "  Failed:  {$failed}\n";
