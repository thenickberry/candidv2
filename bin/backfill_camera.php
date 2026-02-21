#!/usr/bin/env php
<?php

/**
 * Backfill camera field from EXIF data
 *
 * Re-reads EXIF from every original image file and recomputes the camera field
 * using the corrected logic: combine Make + Model, and only attribute a camera
 * when exposure-related EXIF fields are present (excludes scanners/printers).
 *
 * Usage: docker exec candidv2-app-1 php bin/backfill_camera.php [--dry-run]
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap/app.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);

if ($dryRun) {
    echo "DRY RUN — no changes will be written.\n\n";
}

$db = $container->database();
$imagesDir = $container->config('paths.images');

$images = $db->fetchAll(
    "SELECT id, file_path, camera FROM image_info WHERE file_path IS NOT NULL AND deleted_at IS NULL ORDER BY id"
);

$updated = 0;
$cleared = 0;
$unchanged = 0;
$skipped = 0;

foreach ($images as $image) {
    $path = $imagesDir . '/' . $image['file_path'];

    if (!file_exists($path)) {
        echo "  SKIP  #{$image['id']} — file not found: {$image['file_path']}\n";
        $skipped++;
        continue;
    }

    $exif = @exif_read_data($path);
    $newCamera = null;

    if ($exif) {
        $isCamera = isset($exif['ExposureTime']) || isset($exif['FNumber']) || isset($exif['ISOSpeedRatings']);
        if ($isCamera) {
            $make  = trim($exif['Make']  ?? '');
            $model = trim($exif['Model'] ?? '');
            if ($make && $model) {
                $makePrefix = strtok($make, ' ');
                $newCamera = stripos($model, (string)$makePrefix) === 0 ? $model : "$makePrefix $model";
            } elseif ($model) {
                $newCamera = $model;
            } elseif ($make) {
                $newCamera = $make;
            }
        }
    }

    $old = $image['camera'];

    if ($old === $newCamera) {
        $unchanged++;
        continue;
    }

    $label = $newCamera ?? 'NULL';
    $oldLabel = $old ?? 'NULL';

    if ($newCamera === null) {
        echo "  CLEAR #{$image['id']}: \"{$oldLabel}\" → NULL\n";
        $cleared++;
    } else {
        echo "  UPDATE #{$image['id']}: \"{$oldLabel}\" → \"{$newCamera}\"\n";
        $updated++;
    }

    if (!$dryRun) {
        $db->query(
            "UPDATE image_info SET camera = :camera WHERE id = :id",
            ['camera' => $newCamera, 'id' => $image['id']]
        );
    }
}

echo "\nDone.\n";
echo "  Updated:   $updated\n";
echo "  Cleared:   $cleared\n";
echo "  Unchanged: $unchanged\n";
echo "  Skipped:   $skipped\n";

if ($dryRun) {
    echo "\n(Dry run — rerun without --dry-run to apply changes)\n";
}
