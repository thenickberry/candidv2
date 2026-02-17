#!/usr/bin/env php
<?php
/**
 * Migrate images from database BLOBs to filesystem storage
 *
 * Usage: php scripts/migrate_images_to_filesystem.php
 */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$config = require dirname(__DIR__) . '/config/config.php';

// Create database connection
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $config['database']['host'],
    $config['database']['name']
);

$pdo = new PDO($dsn, $config['database']['user'], $config['database']['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Create image storage
$storage = new \App\Service\ImageStorage($config['paths']['images']);

echo "Starting image migration to filesystem...\n\n";

// Get all images that don't have filesystem paths yet
$stmt = $pdo->query("
    SELECT i.id, i.content_type, f.data as file_data, t.data as thumb_data
    FROM image_info i
    LEFT JOIN image_file f ON i.id = f.image_id
    LEFT JOIN image_thumb t ON i.id = t.image_id
    WHERE i.file_path IS NULL AND f.data IS NOT NULL
");

$migrated = 0;
$failed = 0;

while ($row = $stmt->fetch()) {
    $imageId = $row['id'];
    echo "Migrating image #{$imageId}... ";

    try {
        $filePath = null;
        $thumbPath = null;

        // Store full image
        if (!empty($row['file_data'])) {
            $filePath = $storage->store($row['file_data'], 'full');
        }

        // Store thumbnail
        if (!empty($row['thumb_data'])) {
            $thumbPath = $storage->store($row['thumb_data'], 'thumb');
        }

        // Update database with paths
        $updateStmt = $pdo->prepare("
            UPDATE image_info SET file_path = :file_path, thumb_path = :thumb_path WHERE id = :id
        ");
        $updateStmt->execute([
            'file_path' => $filePath,
            'thumb_path' => $thumbPath,
            'id' => $imageId,
        ]);

        echo "OK\n";
        $migrated++;
    } catch (Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\nMigration complete.\n";
echo "Migrated: {$migrated}\n";
echo "Failed: {$failed}\n";

if ($migrated > 0) {
    echo "\nTo free up database space, you can now run:\n";
    echo "  TRUNCATE TABLE image_file;\n";
    echo "  TRUNCATE TABLE image_thumb;\n";
    echo "\nBut only after verifying all images load correctly!\n";
}
