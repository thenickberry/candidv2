<?php

/**
 * Backfill MD5 hashes for existing images
 *
 * This script computes and stores MD5 hashes for all images that don't have one.
 * Used for duplicate detection during uploads.
 *
 * Usage: php scripts/backfill_md5_hashes.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

echo "Backfilling MD5 hashes for existing images...\n\n";

$db = $container->db();
$imageStorage = $container->imageStorage();

// Find images without MD5 hash
$images = $db->fetchAll(
    "SELECT id, file_path FROM image_info WHERE md5_hash IS NULL OR md5_hash = ''"
);

$total = count($images);
echo "Found {$total} images without MD5 hash.\n\n";

if ($total === 0) {
    echo "Nothing to do.\n";
    exit(0);
}

$updated = 0;
$failed = 0;

foreach ($images as $i => $image) {
    $imageId = $image['id'];
    $progress = $i + 1;

    echo "[{$progress}/{$total}] Image #{$imageId}: ";

    $imageData = null;

    // Try filesystem first (new storage)
    if (!empty($image['file_path'])) {
        $imageData = $imageStorage->retrieve($image['file_path']);
    }

    // Fallback to BLOB storage (legacy)
    if (!$imageData) {
        $blob = $db->fetchOne(
            "SELECT data FROM image_file WHERE image_id = :id",
            ['id' => $imageId]
        );
        if ($blob && !empty($blob['data'])) {
            $imageData = $blob['data'];
        }
    }

    if (!$imageData) {
        echo "FAILED (no image data found)\n";
        $failed++;
        continue;
    }

    // Compute MD5 hash
    $md5Hash = md5($imageData);

    // Update the record
    $db->update('image_info', ['md5_hash' => $md5Hash], 'id = :id', ['id' => $imageId]);

    echo "OK ({$md5Hash})\n";
    $updated++;
}

echo "\n";
echo "Complete!\n";
echo "  Updated: {$updated}\n";
echo "  Failed:  {$failed}\n";
