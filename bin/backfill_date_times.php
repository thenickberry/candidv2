<?php

/**
 * Backfill date_taken with time component from EXIF data
 *
 * This script reads the DateTimeOriginal from each image's EXIF data
 * and updates the date_taken field to include the time (H:i:s).
 *
 * Usage: php scripts/backfill_date_times.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

$db = $container->database();
$storage = $container->imageStorage();

echo "Backfilling date_taken with time component from EXIF data...\n\n";

// Get all images with file paths
$images = $db->fetchAll(
    "SELECT id, file_path, date_taken FROM image_info WHERE file_path IS NOT NULL"
);

$total = count($images);
$updated = 0;
$skipped = 0;
$noExif = 0;

echo "Found {$total} images to process.\n\n";

foreach ($images as $index => $image) {
    $num = $index + 1;
    echo "[{$num}/{$total}] Image #{$image['id']}: ";

    try {
        $fullPath = $storage->getFullPath($image['file_path']);

        if (!file_exists($fullPath)) {
            echo "SKIP (file not found)\n";
            $skipped++;
            continue;
        }

        // Read EXIF data
        $exif = @exif_read_data($fullPath);

        if (!$exif || !isset($exif['DateTimeOriginal'])) {
            echo "SKIP (no EXIF DateTimeOriginal)\n";
            $noExif++;
            continue;
        }

        $dateTaken = date('Y-m-d H:i:s', strtotime($exif['DateTimeOriginal']));

        // Check if we already have the same datetime (to avoid unnecessary updates)
        if ($image['date_taken'] === $dateTaken) {
            echo "SKIP (already set)\n";
            $skipped++;
            continue;
        }

        // Update the database
        $db->update('image_info', ['date_taken' => $dateTaken], 'id = :id', ['id' => $image['id']]);

        echo "OK ({$dateTaken})\n";
        $updated++;

    } catch (Exception $e) {
        echo "ERROR ({$e->getMessage()})\n";
        $skipped++;
    }
}

echo "\n";
echo "Done!\n";
echo "  Updated: {$updated}\n";
echo "  Skipped: {$skipped}\n";
echo "  No EXIF: {$noExif}\n";
