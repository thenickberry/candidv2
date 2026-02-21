<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ImageService;
use App\Service\ImageStorage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver;

/**
 * Image Controller
 *
 * Handles image viewing, uploading, and editing.
 */
class ImageController extends Controller
{
    private function imageManager(): ImageManager
    {
        return new ImageManager(new Driver());
    }
    private function imageStorage(): ImageStorage
    {
        return $this->container->imageStorage();
    }

    private function imageService(): ImageService
    {
        return $this->container->imageService();
    }

    public function detail(string $id): string
    {
        $imageId = (int) $id;

        $image = $this->db()->fetchOne(
            "SELECT i.*,
                    u.fname as owner_fname, u.lname as owner_lname,
                    p.fname as photographer_fname, p.lname as photographer_lname
             FROM image_info i
             LEFT JOIN user u ON i.owner = u.id
             LEFT JOIN user p ON i.photographer = p.id
             WHERE i.id = :id AND i.deleted_at IS NULL",
            ['id' => $imageId]
        );

        if (!$image) {
            http_response_code(404);
            return $this->view('errors/404');
        }

        // Check access
        $userAccess = $this->user()['access'] ?? 0;
        $userId = $this->user()['id'] ?? 0;

        if ($image['private'] && $image['owner'] != $userId) {
            http_response_code(403);
            return $this->view('errors/403');
        }

        if ($image['access'] > $userAccess) {
            http_response_code(403);
            return $this->view('errors/403');
        }

        // Increment view count (only once per session)
        $viewedImages = $_SESSION['viewed_images'] ?? [];
        if (!in_array($imageId, $viewedImages, true)) {
            $this->db()->query(
                "UPDATE image_info SET views = views + 1, last_view = NOW() WHERE id = :id",
                ['id' => $imageId]
            );
            $_SESSION['viewed_images'][] = $imageId;
        }

        // Get people tagged in image
        $people = $this->db()->fetchAll(
            "SELECT u.id, u.fname, u.lname
             FROM people p
             JOIN user u ON p.user_id = u.id
             WHERE p.image_id = :image_id",
            ['image_id' => $imageId]
        );

        // Get categories
        $categories = $this->db()->fetchAll(
            "SELECT c.id, c.name, c.parent, ic.pri
             FROM image_category ic
             JOIN category c ON ic.category_id = c.id
             WHERE ic.image_id = :image_id
             ORDER BY ic.pri DESC",
            ['image_id' => $imageId]
        );

        // Build breadcrumb from primary category
        $breadcrumb = [];
        if (!empty($categories)) {
            $primaryCategory = $categories[0];
            $breadcrumb = $this->buildCategoryBreadcrumb($primaryCategory);
        }

        // Get comments
        $comments = $this->db()->fetchAll(
            "SELECT c.*, u.fname, u.lname
             FROM image_comment c
             JOIN user u ON c.user_id = u.id
             WHERE c.image_id = :image_id
             ORDER BY c.stamp ASC",
            ['image_id' => $imageId]
        );

        return $this->view('image/view', [
            'title' => h($image['descr'] ?: 'Image'),
            'image' => $image,
            'people' => $people,
            'categories' => $categories,
            'comments' => $comments,
            'breadcrumb' => $breadcrumb,
            'canEdit' => $userId === (int) $image['owner'] || $this->auth()->isAdmin(),
        ]);
    }

    /**
     * Serve image file
     */
    public function show(string $id): void
    {
        $imageId = (int) $id;
        $size = $this->query('size', 'full'); // full, thumb

        $image = $this->db()->fetchOne(
            "SELECT content_type, file_path, thumb_path FROM image_info WHERE id = :id",
            ['id' => $imageId]
        );

        if (!$image) {
            http_response_code(404);
            exit;
        }

        $pathColumn = $size === 'thumb' ? 'thumb_path' : 'file_path';

        // Try filesystem first (new storage)
        if (!empty($image[$pathColumn])) {
            $data = $this->imageStorage()->retrieve($image[$pathColumn]);
            if ($data) {
                header('Content-Type: ' . ($image['content_type'] ?: 'image/jpeg'));
                header('Cache-Control: public, max-age=86400');
                echo $data;
                exit;
            }
        }

        // Fallback to BLOB storage (legacy)
        $table = $size === 'thumb' ? 'image_thumb' : 'image_file';
        $blob = $this->db()->fetchOne(
            "SELECT data FROM {$table} WHERE image_id = :id",
            ['id' => $imageId]
        );

        if (!$blob || empty($blob['data'])) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . ($image['content_type'] ?: 'image/jpeg'));
        header('Cache-Control: public, max-age=86400');
        echo $blob['data'];
        exit;
    }

    public function showAdd(): string
    {
        $this->requireAuth();

        $categories = $this->getCategories();
        $selectedCategoryId = $this->query('category') ? (int) $this->query('category') : null;

        return $this->view('image/add', [
            'title' => 'Add Image',
            'categories' => $categories,
            'selectedCategoryId' => $selectedCategoryId,
        ]);
    }

    public function showEdit(string $id): string
    {
        $this->requireAuth();

        $imageId = (int) $id;
        $image = $this->db()->fetchOne(
            "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
            ['id' => $imageId]
        );

        if (!$image) {
            http_response_code(404);
            return $this->view('errors/404');
        }

        // Check permission
        $userId = $this->user()['id'] ?? 0;
        if ((int)$image['owner'] !== $userId && !$this->auth()->isAdmin()) {
            http_response_code(403);
            return $this->view('errors/403');
        }

        $categories = $this->getCategories();

        // Get current categories for this image
        $imageCategories = $this->db()->fetchAll(
            "SELECT category_id FROM image_category WHERE image_id = :id",
            ['id' => $imageId]
        );
        $imageCategoryIds = array_column($imageCategories, 'category_id');

        // Get all users for people tagging
        $users = $this->db()->fetchAll(
            "SELECT id, fname, lname, username FROM user WHERE onlist = 'y' ORDER BY lname, fname"
        );

        // Get currently tagged people
        $taggedPeople = $this->db()->fetchAll(
            "SELECT user_id FROM people WHERE image_id = :id",
            ['id' => $imageId]
        );
        $taggedPeopleIds = array_column($taggedPeople, 'user_id');

        return $this->view('image/edit', [
            'title' => 'Edit Image',
            'image' => $image,
            'categories' => $categories,
            'imageCategoryIds' => $imageCategoryIds,
            'users' => $users,
            'taggedPeopleIds' => $taggedPeopleIds,
            'returnCategory' => $this->query('return_category'),
        ]);
    }

    public function edit(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $imageId = (int) $id;
        $image = $this->db()->fetchOne(
            "SELECT owner FROM image_info WHERE id = :id AND deleted_at IS NULL",
            ['id' => $imageId]
        );

        if (!$image) {
            $this->flash('error', 'Image not found.');
            $this->redirect('/');
        }

        // Check permission
        $userId = $this->user()['id'] ?? 0;
        if ((int)$image['owner'] !== $userId && !$this->auth()->isAdmin()) {
            $this->flash('error', 'Access denied.');
            $this->redirect('/image/' . $imageId);
        }

        // Update image info
        $this->db()->update('image_info', [
            'descr' => $this->input('description', ''),
            'date_taken' => $this->input('date_taken') ?: null,
            'access' => (int) $this->input('access', 0),
            'private' => $this->input('private') ? 1 : 0,
        ], 'id = :id', ['id' => $imageId]);

        // Update categories
        $this->db()->delete('image_category', 'image_id = :id', ['id' => $imageId]);
        $categoryId = $this->input('category_id');
        if ($categoryId) {
            $this->db()->insert('image_category', [
                'image_id' => $imageId,
                'category_id' => (int) $categoryId,
                'pri' => 'y',
            ]);
        }

        // Update people tags
        $this->db()->delete('people', 'image_id = :id', ['id' => $imageId]);
        $peopleIds = $_POST['people'] ?? [];
        if (is_array($peopleIds)) {
            foreach ($peopleIds as $personId) {
                $this->db()->insert('people', [
                    'image_id' => $imageId,
                    'user_id' => (int) $personId,
                ]);
            }
        }

        $this->flash('success', 'Image updated.');

        // Redirect to return category if specified, otherwise to image
        $returnCategory = $this->input('return_category');
        if ($returnCategory) {
            $this->redirect('/browse/' . (int) $returnCategory);
        } else {
            $this->redirect('/image/' . $imageId);
        }
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $imageId = (int) $id;
        $image = $this->imageService()->find($imageId);

        if (!$image) {
            $this->flash('error', 'Image not found.');
            $this->redirect('/');
        }

        // Check permission
        $userId = $this->user()['id'] ?? 0;
        if ((int)$image['owner'] !== $userId && !$this->auth()->isAdmin()) {
            $this->flash('error', 'Access denied.');
            $this->redirect('/image/' . $imageId);
        }

        // Soft-delete the image
        if ($this->imageService()->softDelete($imageId, $userId)) {
            $this->flash('success', 'Image moved to trash.');
        } else {
            $this->flash('error', 'Failed to delete image.');
        }

        // Redirect to return category if specified, otherwise to home
        $returnCategory = $this->input('return_category');
        if ($returnCategory) {
            $this->redirect('/browse/' . (int) $returnCategory);
        } else {
            $this->redirect('/');
        }
    }

    public function rotate(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $imageId = (int) $id;
        $image = $this->db()->fetchOne(
            "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
            ['id' => $imageId]
        );

        if (!$image) {
            $this->flash('error', 'Image not found.');
            $this->redirect('/');
        }

        // Check permission
        $userId = $this->user()['id'] ?? 0;
        if ((int)$image['owner'] !== $userId && !$this->auth()->isAdmin()) {
            $this->flash('error', 'Access denied.');
            $this->redirect('/image/' . $imageId);
        }

        $direction = $this->input('direction', 'cw');
        $degrees = $direction === 'ccw' ? -90 : 90;

        // Get image file
        if (empty($image['file_path'])) {
            $this->flash('error', 'Image file not found.');
            $this->redirect('/image/' . $imageId . '/edit');
        }

        $imageData = $this->imageStorage()->retrieve($image['file_path']);
        if (!$imageData) {
            $this->flash('error', 'Could not read image file.');
            $this->redirect('/image/' . $imageId . '/edit');
        }

        try {
            $img = $this->imageManager()->read($imageData);
            $img->rotate($degrees);

            // Get new dimensions
            $newWidth = $img->width();
            $newHeight = $img->height();

            // Encode based on content type
            $rotatedData = match ($image['content_type']) {
                'image/png' => $img->toPng()->toString(),
                'image/gif' => $img->toGif()->toString(),
                default => $img->toJpeg(100)->toString(),
            };

            // Create thumbnail
            $thumbData = $this->createThumbnail($img);
        } catch (\Exception $e) {
            $this->flash('error', 'Failed to rotate image.');
            $this->redirect('/image/' . $imageId . '/edit');
        }

        // Delete old file and store new one
        $this->imageStorage()->delete($image['file_path']);
        $newFilePath = $this->imageStorage()->store($rotatedData, 'full');

        // Delete old thumbnail and store new one
        if (!empty($image['thumb_path'])) {
            $this->imageStorage()->delete($image['thumb_path']);
        }
        $newThumbPath = $this->imageStorage()->store($thumbData, 'thumb');

        // Update database
        $this->db()->update('image_info', [
            'file_path' => $newFilePath,
            'thumb_path' => $newThumbPath,
            'width' => $newWidth,
            'height' => $newHeight,
            'md5_hash' => md5($rotatedData),
        ], 'id = :id', ['id' => $imageId]);

        $this->flash('success', 'Image rotated.');

        $returnCategory = $this->input('return_category');
        $redirectUrl = '/image/' . $imageId . '/edit';
        if ($returnCategory) {
            $redirectUrl .= '?return_category=' . (int) $returnCategory;
        }
        $this->redirect($redirectUrl);
    }

    /**
     * Create thumbnail from an Intervention Image instance or file path
     *
     * @param \Intervention\Image\Interfaces\ImageInterface|string $source Image instance or file path
     */
    private function createThumbnail(\Intervention\Image\Interfaces\ImageInterface|string $source): string
    {
        $maxWidth = $this->config('images.thumb_width', 400);
        $maxHeight = $this->config('images.thumb_height', 400);
        $quality = $this->config('images.thumb_quality', 100);

        // If source is a path, read it
        if (is_string($source)) {
            $img = $this->imageManager()->read($source);
        } else {
            // Clone to avoid modifying original
            $img = clone $source;
        }

        return $img
            ->scaleDown($maxWidth, $maxHeight)
            ->toJpeg($quality)
            ->toString();
    }

    public function add(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        // Handle multiple file uploads
        if (empty($_FILES['images']) || !is_array($_FILES['images']['name'])) {
            $this->flash('error', 'Please select at least one image to upload.');
            $this->redirect('/image/add');
        }

        $files = $this->normalizeFilesArray($_FILES['images']);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/heic', 'image/heif'];
        $categoryId = $this->input('category_id');
        $isPrivate = $this->input('private') ? 1 : 0;
        $useFilename = $this->input('use_filename') ? true : false;
        $userId = $this->user()['id'];

        $uploaded = 0;
        $failed = 0;
        $duplicates = 0;
        $lastImageId = null;
        $failedFiles = [];
        $duplicateFiles = [];

        foreach ($files as $file) {
            $filename = $file['name'] ?? 'unknown';

            // Skip files with errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $failed++;
                $failedFiles[] = $filename . ' (upload error)';
                continue;
            }

            // Validate file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                $failed++;
                $failedFiles[] = $filename . ' (unsupported type)';
                continue;
            }

            // Check for duplicate in category
            if ($categoryId) {
                $md5Hash = md5_file($file['tmp_name']);
                $existingImage = $this->db()->fetchOne(
                    "SELECT i.id FROM image_info i
                     JOIN image_category ic ON i.id = ic.image_id
                     WHERE i.md5_hash = :hash AND ic.category_id = :category_id",
                    ['hash' => $md5Hash, 'category_id' => (int) $categoryId]
                );
                if ($existingImage) {
                    $duplicates++;
                    $duplicateFiles[] = $filename;
                    continue;
                }
            }

            // Process the image
            $imageId = $this->processUploadedImage($file, $mimeType, $categoryId, $isPrivate, $userId, $useFilename);

            if ($imageId) {
                $uploaded++;
                $lastImageId = $imageId;
            } else {
                $failed++;
                $failedFiles[] = $filename . ' (processing error)';
            }
        }

        // Flash appropriate message
        if ($uploaded === 0 && $duplicates === 0) {
            $this->flash('error', 'No images were uploaded. Please check file types (JPEG, PNG, GIF, HEIC only).', $failedFiles);
            $this->redirect('/image/add');
        } else {
            $messages = [];
            if ($uploaded > 0) {
                $messages[] = $uploaded === 1 ? '1 image uploaded' : "{$uploaded} images uploaded";
            }
            if ($duplicates > 0) {
                $messages[] = $duplicates === 1 ? '1 duplicate skipped' : "{$duplicates} duplicates skipped";
            }
            if ($failed > 0) {
                $messages[] = $failed === 1 ? '1 file failed' : "{$failed} files failed";
            }

            // Build details array
            $details = [];
            if (!empty($duplicateFiles)) {
                $details[] = 'Duplicates: ' . implode(', ', $duplicateFiles);
            }
            if (!empty($failedFiles)) {
                $details[] = 'Failed: ' . implode(', ', $failedFiles);
            }

            $this->flash($uploaded > 0 ? 'success' : 'error', implode('. ', $messages) . '.', $details);
        }

        // Redirect to the last uploaded image, or browse if multiple
        if ($uploaded === 1 && $lastImageId) {
            $this->redirect('/image/' . $lastImageId);
        } elseif ($categoryId) {
            $this->redirect('/browse/' . $categoryId);
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Process a single uploaded image file
     */
    private function processUploadedImage(array $file, string $mimeType, ?string $categoryId, int $isPrivate, int $userId, bool $useFilename = false): ?int
    {
        try {
            $sourcePath = $file['tmp_name'];

            // Read image with Intervention (handles HEIC, EXIF orientation automatically)
            $img = $this->imageManager()->read($sourcePath);

            // Convert HEIC/HEIF to JPEG for storage
            if (in_array($mimeType, ['image/heic', 'image/heif'])) {
                $imageData = $img->toJpeg(95)->toString();
                $mimeType = 'image/jpeg';
            } else {
                $imageData = file_get_contents($sourcePath);
            }

            // Get dimensions
            $width = $img->width();
            $height = $img->height();

            // Calculate MD5 hash for duplicate detection
            $md5Hash = md5($imageData);

            // Create thumbnail
            $thumbData = $this->createThumbnail($img);

            // Store images on filesystem
            $filePath = $this->imageStorage()->store($imageData, 'full');
            $thumbPath = $this->imageStorage()->store($thumbData, 'thumb');

            // Read EXIF data for date and camera
            $exif = @exif_read_data($file['tmp_name']);
            $dateTaken = null;
            $camera = null;

            if ($exif) {
                if (isset($exif['DateTimeOriginal'])) {
                    $dateTaken = date('Y-m-d H:i:s', strtotime($exif['DateTimeOriginal']));
                }
                // Only attribute a camera when exposure-related fields are present.
                // Scanners and printers embed Make/Model in EXIF but lack these fields.
                $isCamera = isset($exif['ExposureTime']) || isset($exif['FNumber']) || isset($exif['ISOSpeedRatings']);
                if ($isCamera) {
                    $make  = trim($exif['Make']  ?? '');
                    $model = trim($exif['Model'] ?? '');
                    if ($make && $model) {
                        // Use only the first word of make for deduplication
                        // e.g. "NIKON CORPORATION" + "NIKON D700" → "NIKON D700"
                        //      "Apple" + "iPhone 15 Pro Max" → "Apple iPhone 15 Pro Max"
                        $makePrefix = strtok($make, ' ');
                        $camera = stripos($model, (string)$makePrefix) === 0 ? $model : "$makePrefix $model";
                    } elseif ($model) {
                        $camera = $model;
                    } elseif ($make) {
                        $camera = $make;
                    }
                }
            }

            // Get sanitized filename
            $filename = $this->sanitizeFilename($file['name']);

            // Only use filename as description if explicitly requested
            $description = null;
            if ($useFilename) {
                $description = pathinfo($filename, PATHINFO_FILENAME);
                // Clean up common patterns in filenames
                $description = str_replace(['_', '-'], ' ', $description);
                $description = preg_replace('/\s+/', ' ', trim($description));
            }

            // Insert image record
            $imageId = $this->db()->insert('image_info', [
                'descr' => $description,
                'owner' => $userId,
                'photographer' => $userId,
                'date_taken' => $dateTaken,
                'category_id' => $categoryId ?: null,
                'access' => 0,
                'private' => $isPrivate,
                'width' => $width,
                'height' => $height,
                'content_type' => $mimeType,
                'filename' => $filename,
                'camera' => $camera,
                'file_path' => $filePath,
                'thumb_path' => $thumbPath,
                'md5_hash' => $md5Hash,
                'added' => date('Y-m-d H:i:s'),
            ]);

            // Link to category
            if ($categoryId) {
                $this->db()->insert('image_category', [
                    'image_id' => $imageId,
                    'category_id' => (int) $categoryId,
                    'pri' => 'y',
                ]);
            }

            return $imageId;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Normalize the $_FILES array for multiple uploads
     */
    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];

        if (!is_array($files['name'])) {
            // Single file upload
            return [$files];
        }

        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            $normalized[] = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
        }

        return $normalized;
    }

    /**
     * Get categories for dropdown (excludes soft-deleted)
     */
    private function getCategories(?int $parentId = null, int $depth = 0): array
    {
        $categories = [];

        $sql = "SELECT id, name FROM category WHERE deleted_at IS NULL AND parent " .
               ($parentId ? "= :parent" : "IS NULL") . " ORDER BY name";

        $rows = $this->db()->fetchAll($sql, $parentId ? ['parent' => $parentId] : []);

        foreach ($rows as $row) {
            $row['depth'] = $depth;
            $row['indent'] = str_repeat('— ', $depth);
            $categories[] = $row;

            // Recursively get children
            $children = $this->getCategories((int) $row['id'], $depth + 1);
            $categories = array_merge($categories, $children);
        }

        return $categories;
    }

    /**
     * Build breadcrumb path for a category
     */
    private function buildCategoryBreadcrumb(array $category): array
    {
        $breadcrumb = [];
        $current = $category;

        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current['id'],
                'name' => $current['name'],
            ]);

            if (!empty($current['parent'])) {
                $current = $this->db()->fetchOne(
                    "SELECT id, name, parent FROM category WHERE id = :id",
                    ['id' => $current['parent']]
                );
            } else {
                $current = null;
            }
        }

        return $breadcrumb;
    }

    /**
     * Sanitize a filename for safe storage
     */
    private function sanitizeFilename(string $filename): string
    {
        // Get just the filename without path
        $filename = basename($filename);

        // Remove null bytes and control characters
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);

        // Replace dangerous characters
        $filename = preg_replace('/[\/\\\\:*?"<>|]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($ext)) . '.' . $ext;
        }

        return $filename ?: 'unnamed';
    }

    /**
     * Show bulk edit form
     */
    public function showBulkEdit(): string
    {
        $this->requireAuth();

        $idsParam = $this->query('ids', '');
        $ids = array_filter(array_map('intval', explode(',', $idsParam)));

        if (empty($ids)) {
            $this->flash('error', 'No images selected.');
            $this->redirect('/');
        }

        // Fetch images and verify permission for each
        $images = [];
        foreach ($ids as $id) {
            $image = $this->db()->fetchOne(
                "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
                ['id' => $id]
            );
            if ($image && $this->canEditImage($image)) {
                $images[] = $image;
            }
        }

        if (empty($images)) {
            $this->flash('error', 'No editable images found.');
            $this->redirect('/');
        }

        $categories = $this->getCategories();
        $users = $this->db()->fetchAll(
            "SELECT id, fname, lname, username FROM user WHERE onlist = 'y' ORDER BY lname, fname"
        );

        return $this->view('image/bulk-edit', [
            'title' => 'Bulk Edit Images',
            'images' => $images,
            'categories' => $categories,
            'users' => $users,
            'returnUrl' => $this->query('return_url', '/'),
        ]);
    }

    /**
     * Process bulk edit
     */
    public function bulkEdit(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $ids = (array) $this->input('image_ids', []);
        $updated = 0;

        foreach ($ids as $id) {
            $imageId = (int) $id;
            $image = $this->db()->fetchOne(
                "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
                ['id' => $imageId]
            );

            if (!$image || !$this->canEditImage($image)) {
                continue;
            }

            $updates = [];

            // Only update fields that were provided and not empty
            $description = $this->input('description');
            if ($description !== null && $description !== '') {
                $updates['descr'] = $description;
            }

            $dateTaken = $this->input('date_taken');
            if ($dateTaken !== null && $dateTaken !== '') {
                $updates['date_taken'] = $dateTaken;
            }

            $access = $this->input('access');
            if ($access !== null && $access !== '') {
                $updates['access'] = (int) $access;
            }

            $private = $this->input('private');
            if ($private !== null && $private !== '') {
                $updates['private'] = (int) $private;
            }

            $photographer = $this->input('photographer');
            if ($photographer !== null && $photographer !== '') {
                $updates['photographer'] = (int) $photographer;
            }

            if (!empty($updates)) {
                $this->db()->update('image_info', $updates, 'id = :id', ['id' => $imageId]);
            }

            // Handle category change
            $categoryId = $this->input('category_id');
            if ($categoryId !== null && $categoryId !== '') {
                $this->db()->delete('image_category', 'image_id = :id', ['id' => $imageId]);
                if ((int) $categoryId > 0) {
                    $this->db()->insert('image_category', [
                        'image_id' => $imageId,
                        'category_id' => (int) $categoryId,
                        'pri' => 'y',
                    ]);
                }
            }

            // Handle add people
            $addPeople = $this->input('add_people') ?? [];
            if (is_array($addPeople)) {
                foreach ($addPeople as $personId) {
                    // Check if already tagged
                    $exists = $this->db()->fetchOne(
                        "SELECT 1 FROM people WHERE image_id = :iid AND user_id = :uid",
                        ['iid' => $imageId, 'uid' => (int) $personId]
                    );
                    if (!$exists) {
                        $this->db()->insert('people', [
                            'image_id' => $imageId,
                            'user_id' => (int) $personId,
                        ]);
                    }
                }
            }

            // Handle remove people
            $removePeople = $this->input('remove_people') ?? [];
            if (is_array($removePeople)) {
                foreach ($removePeople as $personId) {
                    $this->db()->delete(
                        'people',
                        'image_id = :iid AND user_id = :uid',
                        ['iid' => $imageId, 'uid' => (int) $personId]
                    );
                }
            }

            $updated++;
        }

        if ($updated > 0) {
            $this->flash('success', "Updated {$updated} image(s).");
        } else {
            $this->flash('error', 'No images were updated.');
        }

        $returnUrl = $this->input('return_url', '/');
        $this->redirect($returnUrl);
    }

    /**
     * Process bulk edit via JSON
     */
    public function bulkEditJson(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        header('Content-Type: application/json');

        $ids = (array) $this->input('image_ids', []);
        $updated = 0;

        foreach ($ids as $id) {
            $imageId = (int) $id;
            $image = $this->db()->fetchOne(
                "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
                ['id' => $imageId]
            );

            if (!$image || !$this->canEditImage($image)) {
                continue;
            }

            $updates = [];

            $description = $this->input('description');
            if ($description !== null && $description !== '') {
                $updates['descr'] = $description;
            }

            $dateTaken = $this->input('date_taken');
            if ($dateTaken !== null && $dateTaken !== '') {
                $updates['date_taken'] = $dateTaken;
            }

            $access = $this->input('access');
            if ($access !== null && $access !== '') {
                $updates['access'] = (int) $access;
            }

            $private = $this->input('private');
            if ($private !== null && $private !== '') {
                $updates['private'] = (int) $private;
            }

            $photographer = $this->input('photographer');
            if ($photographer !== null && $photographer !== '') {
                $updates['photographer'] = (int) $photographer;
            }

            if (!empty($updates)) {
                $this->db()->update('image_info', $updates, 'id = :id', ['id' => $imageId]);
            }

            $categoryId = $this->input('category_id');
            if ($categoryId !== null && $categoryId !== '') {
                $this->db()->delete('image_category', 'image_id = :id', ['id' => $imageId]);
                if ((int) $categoryId > 0) {
                    $this->db()->insert('image_category', [
                        'image_id' => $imageId,
                        'category_id' => (int) $categoryId,
                        'pri' => 'y',
                    ]);
                }
            }

            $addPeople = $this->input('add_people') ?? [];
            if (is_array($addPeople)) {
                foreach ($addPeople as $personId) {
                    $exists = $this->db()->fetchOne(
                        "SELECT 1 FROM people WHERE image_id = :iid AND user_id = :uid",
                        ['iid' => $imageId, 'uid' => (int) $personId]
                    );
                    if (!$exists) {
                        $this->db()->insert('people', [
                            'image_id' => $imageId,
                            'user_id' => (int) $personId,
                        ]);
                    }
                }
            }

            $removePeople = $this->input('remove_people') ?? [];
            if (is_array($removePeople)) {
                foreach ($removePeople as $personId) {
                    $this->db()->delete(
                        'people',
                        'image_id = :iid AND user_id = :uid',
                        ['iid' => $imageId, 'uid' => (int) $personId]
                    );
                }
            }

            $updated++;
        }

        echo json_encode([
            'success' => true,
            'updated' => $updated,
        ]);
    }

    /**
     * Bulk rotate images
     */
    public function bulkRotate(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $ids = (array) $this->input('images', []);
        $rotated = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $imageId = (int) $id;
            $image = $this->db()->fetchOne(
                "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
                ['id' => $imageId]
            );

            if (!$image || !$this->canEditImage($image)) {
                $failed++;
                continue;
            }

            // Skip images without file paths
            if (empty($image['file_path'])) {
                $failed++;
                continue;
            }

            $imageData = $this->imageStorage()->retrieve($image['file_path']);
            if (!$imageData) {
                $failed++;
                continue;
            }

            try {
                // Use Intervention Image for rotation
                $img = $this->imageManager()->read($imageData);
                $img->rotate(90); // Clockwise

                // Get new dimensions
                $newWidth = $img->width();
                $newHeight = $img->height();

                // Encode based on content type
                $rotatedData = match ($image['content_type']) {
                    'image/png' => $img->toPng()->toString(),
                    'image/gif' => $img->toGif()->toString(),
                    default => $img->toJpeg(100)->toString(),
                };

                // Create thumbnail
                $thumbData = $this->createThumbnail($img);

                // Delete old file and store new one
                $this->imageStorage()->delete($image['file_path']);
                $newFilePath = $this->imageStorage()->store($rotatedData, 'full');

                // Delete old thumbnail and store new one
                if (!empty($image['thumb_path'])) {
                    $this->imageStorage()->delete($image['thumb_path']);
                }
                $newThumbPath = $this->imageStorage()->store($thumbData, 'thumb');

                // Update database
                $this->db()->update('image_info', [
                    'file_path' => $newFilePath,
                    'thumb_path' => $newThumbPath,
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'md5_hash' => md5($rotatedData),
                ], 'id = :id', ['id' => $imageId]);

                $rotated++;
            } catch (\Exception $e) {
                $failed++;
                continue;
            }
        }

        if ($rotated > 0) {
            $message = "Rotated {$rotated} image(s).";
            if ($failed > 0) {
                $message .= " {$failed} failed.";
            }
            $this->flash('success', $message);
        } else {
            $this->flash('error', 'No images were rotated.');
        }

        $returnUrl = $this->input('return_url', '/');
        $this->redirect($returnUrl);
    }

    /**
     * Bulk delete images
     */
    public function bulkDelete(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $ids = (array) $this->input('image_ids', []);
        $deleted = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $imageId = (int) $id;
            $image = $this->db()->fetchOne(
                "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
                ['id' => $imageId]
            );

            if (!$image || !$this->canEditImage($image)) {
                $failed++;
                continue;
            }

            // Soft delete the image
            $this->db()->update('image_info', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => $this->user()['id'],
            ], 'id = :id', ['id' => $imageId]);

            $deleted++;
        }

        if ($deleted > 0) {
            $message = "Deleted {$deleted} image(s).";
            if ($failed > 0) {
                $message .= " {$failed} failed.";
            }
            $this->flash('success', $message);
        } else {
            $this->flash('error', 'No images were deleted.');
        }

        $returnUrl = $this->input('return_url', '/');
        $this->redirect($returnUrl);
    }

    /**
     * Check if current user can edit an image
     */
    private function canEditImage(array $image): bool
    {
        $userId = $this->user()['id'] ?? 0;
        return (int) $image['owner'] === $userId || $this->auth()->isAdmin();
    }

    /**
     * Get image data as JSON for modal editing
     */
    public function getJson(string $id): void
    {
        header('Content-Type: application/json');

        if (!$this->auth()->check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        $imageId = (int) $id;
        $image = $this->db()->fetchOne(
            "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
            ['id' => $imageId]
        );

        if (!$image) {
            http_response_code(404);
            echo json_encode(['error' => 'Image not found']);
            exit;
        }

        if (!$this->canEditImage($image)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        // Get categories
        $categories = $this->getCategories();

        // Get current category for this image
        $imageCategory = $this->db()->fetchOne(
            "SELECT category_id FROM image_category WHERE image_id = :id AND pri = 'y'",
            ['id' => $imageId]
        );

        // Get all users for people tagging
        $users = $this->db()->fetchAll(
            "SELECT id, fname, lname, username FROM user WHERE onlist = 'y' ORDER BY lname, fname"
        );

        // Get currently tagged people
        $taggedPeople = $this->db()->fetchAll(
            "SELECT user_id FROM people WHERE image_id = :id",
            ['id' => $imageId]
        );
        $taggedPeopleIds = array_column($taggedPeople, 'user_id');

        echo json_encode([
            'image' => [
                'id' => (int) $image['id'],
                'descr' => $image['descr'] ?? '',
                'date_taken' => $image['date_taken'] ? substr($image['date_taken'], 0, 10) : '',
                'access' => (int) $image['access'],
                'private' => (int) $image['private'],
                'category_id' => $imageCategory ? (int) $imageCategory['category_id'] : null,
            ],
            'categories' => array_map(fn($c) => [
                'id' => (int) $c['id'],
                'name' => $c['indent'] . $c['name'],
            ], $categories),
            'users' => array_map(fn($u) => [
                'id' => (int) $u['id'],
                'name' => trim(($u['fname'] ?? '') . ' ' . ($u['lname'] ?? '')) ?: $u['username'],
            ], $users),
            'taggedPeopleIds' => array_map('intval', $taggedPeopleIds),
            'csrfToken' => csrf_token(),
        ]);
        exit;
    }

    /**
     * Update image via JSON API
     */
    public function editJson(string $id): void
    {
        header('Content-Type: application/json');

        if (!$this->auth()->check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        $this->validateCsrf();

        $imageId = (int) $id;
        $image = $this->db()->fetchOne(
            "SELECT * FROM image_info WHERE id = :id AND deleted_at IS NULL",
            ['id' => $imageId]
        );

        if (!$image) {
            http_response_code(404);
            echo json_encode(['error' => 'Image not found']);
            exit;
        }

        if (!$this->canEditImage($image)) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        // Update image info
        $this->db()->update('image_info', [
            'descr' => $this->input('description', ''),
            'date_taken' => $this->input('date_taken') ?: null,
            'access' => (int) $this->input('access', 0),
            'private' => $this->input('private') ? 1 : 0,
        ], 'id = :id', ['id' => $imageId]);

        // Update categories
        $this->db()->delete('image_category', 'image_id = :id', ['id' => $imageId]);
        $categoryId = $this->input('category_id');
        if ($categoryId) {
            $this->db()->insert('image_category', [
                'image_id' => $imageId,
                'category_id' => (int) $categoryId,
                'pri' => 'y',
            ]);
        }

        // Update people tags
        $this->db()->delete('people', 'image_id = :id', ['id' => $imageId]);
        $peopleIds = $_POST['people'] ?? [];
        if (is_array($peopleIds)) {
            foreach ($peopleIds as $personId) {
                $this->db()->insert('people', [
                    'image_id' => $imageId,
                    'user_id' => (int) $personId,
                ]);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Image updated',
            'image' => [
                'id' => $imageId,
                'descr' => $this->input('description', ''),
            ],
        ]);
        exit;
    }
}
