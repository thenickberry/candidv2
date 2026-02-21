<?php

declare(strict_types=1);

/**
 * Clean up programmatic/nonsense descriptions from image_info
 *
 * Patterns to clear:
 * - UUID-like strings (Apple Live Photo IDs): "01D42551 4EFF 4A68 A978 3E5677DAE395"
 * - IMG #### patterns from camera filenames
 *
 * Usage:
 *   php scripts/cleanup_descriptions.php           # Dry run (preview changes)
 *   php scripts/cleanup_descriptions.php --execute # Actually update database
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Service\Database;

$config = require dirname(__DIR__) . '/config/config.php';
$db = new Database(
    $config['database']['host'],
    $config['database']['name'],
    $config['database']['user'],
    $config['database']['pass']
);

$dryRun = !in_array('--execute', $argv);

echo "Cleaning up programmatic descriptions...\n";
if ($dryRun) {
    echo "(DRY RUN - use --execute to apply changes)\n";
}
echo "\n";

// Patterns to match for clearing
$patterns = [
    // UUID-like strings (with spaces instead of dashes, possibly with extra text)
    // e.g., "01D42551 4EFF 4A68 A978 3E5677DAE395" or "01D42551 4EFF 4A68 A978 3E5677DAE395 1 201 a"
    '/^[0-9A-F]{8} [0-9A-F]{4} [0-9A-F]{4} [0-9A-F]{4} [0-9A-F]{12}/i',

    // IMG #### patterns (camera default filenames)
    '/^IMG[_ ]\d+$/i',

    // DSC #### patterns (another camera default)
    '/^DSC[_ ]?\d+$/i',

    // DSCN #### patterns (Nikon)
    '/^DSCN?\d+$/i',

    // P#### patterns (some cameras)
    '/^P\d{4,}$/i',

    // Screenshot patterns
    '/^Screenshot \d{4}-\d{2}-\d{2}/i',

    // Just numbers
    '/^\d+$/',
];

// Fetch all descriptions
$results = $db->fetchAll(
    "SELECT id, descr FROM image_info WHERE descr IS NOT NULL AND descr != '' ORDER BY id"
);

$toUpdate = [];

foreach ($results as $row) {
    $descr = $row['descr'];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $descr)) {
            $toUpdate[] = $row;
            break;
        }
    }
}

if (empty($toUpdate)) {
    echo "No programmatic descriptions found.\n";
    exit(0);
}

echo "Found " . count($toUpdate) . " descriptions to clear:\n\n";

foreach ($toUpdate as $row) {
    echo "  ID {$row['id']}: \"{$row['descr']}\"\n";
}

echo "\n";

if ($dryRun) {
    echo "Run with --execute to clear these descriptions.\n";
} else {
    $ids = array_column($toUpdate, 'id');

    // Update in batches to avoid overly long queries
    $batchSize = 100;
    $updated = 0;

    foreach (array_chunk($ids, $batchSize) as $batch) {
        $placeholders = implode(',', array_fill(0, count($batch), '?'));
        $db->query(
            "UPDATE image_info SET descr = NULL WHERE id IN ($placeholders)",
            $batch
        );
        $updated += count($batch);
    }

    echo "Cleared {$updated} descriptions.\n";
}
