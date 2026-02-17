<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\ImageStorage;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for ImageStorage service
 *
 * Tests image storage against a real filesystem.
 */
class ImageStorageTest extends TestCase
{
    private string $testStoragePath;
    private ?ImageStorage $storage = null;
    private array $createdFiles = [];

    protected function setUp(): void
    {
        // Create temp directory for testing
        $this->testStoragePath = sys_get_temp_dir() . '/candid_test_' . uniqid();
        mkdir($this->testStoragePath, 0755, true);

        $this->storage = new ImageStorage($this->testStoragePath);
    }

    protected function tearDown(): void
    {
        // Clean up created files
        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        // Remove test directory recursively
        $this->removeDirectory($this->testStoragePath);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testStoreCreatesFile(): void
    {
        $data = 'test image data';

        $path = $this->storage->store($data, 'full');

        $this->assertNotEmpty($path);
        $fullPath = $this->testStoragePath . '/' . $path;
        $this->assertFileExists($fullPath);
        $this->createdFiles[] = $fullPath;
    }

    public function testStoreCreatesHashBasedDirectoryStructure(): void
    {
        $data = 'unique data ' . uniqid();

        $path = $this->storage->store($data, 'full');

        // Path should contain hash-based subdirectories (format: ab/cd/hash.jpg)
        $this->assertMatchesRegularExpression('/^[a-f0-9]{2}\/[a-f0-9]{2}\/[a-f0-9]{64}\.jpg$/', $path);
    }

    public function testStoreDifferentTypesHaveDifferentSuffixes(): void
    {
        $data = 'same data';

        $fullPath = $this->storage->store($data, 'full');
        $thumbPath = $this->storage->store($data, 'thumb');

        // Full images end with .jpg, thumbnails end with _thumb.jpg
        $this->assertStringEndsWith('.jpg', $fullPath);
        $this->assertStringEndsWith('_thumb.jpg', $thumbPath);
        $this->assertStringNotContainsString('_thumb', $fullPath);
    }

    public function testRetrieveReturnsStoredData(): void
    {
        $originalData = 'this is test image binary data ' . random_bytes(100);

        $path = $this->storage->store($originalData, 'full');
        $retrieved = $this->storage->retrieve($path);

        $this->assertEquals($originalData, $retrieved);
    }

    public function testRetrieveReturnsNullForMissingFile(): void
    {
        $result = $this->storage->retrieve('nonexistent/path/file.bin');

        $this->assertNull($result);
    }

    public function testDeleteRemovesFile(): void
    {
        $data = 'data to delete';
        $path = $this->storage->store($data, 'full');
        $fullPath = $this->testStoragePath . '/' . $path;

        $this->assertFileExists($fullPath);

        $result = $this->storage->delete($path);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($fullPath);
    }

    public function testDeleteReturnsFalseForMissingFile(): void
    {
        $result = $this->storage->delete('nonexistent/path/file.bin');

        $this->assertFalse($result);
    }

    public function testExistsReturnsTrueForExistingFile(): void
    {
        $data = 'test data';
        $path = $this->storage->store($data, 'full');

        $this->assertTrue($this->storage->exists($path));
    }

    public function testExistsReturnsFalseForMissingFile(): void
    {
        $this->assertFalse($this->storage->exists('nonexistent/file.bin'));
    }

    public function testStoreBinaryData(): void
    {
        // Simulate a real image (PNG header + random bytes)
        $pngHeader = hex2bin('89504E470D0A1A0A');
        $data = $pngHeader . random_bytes(1000);

        $path = $this->storage->store($data, 'full');
        $retrieved = $this->storage->retrieve($path);

        $this->assertEquals($data, $retrieved);
    }

    public function testMultipleStoresCreateUniqueFiles(): void
    {
        $paths = [];
        for ($i = 0; $i < 5; $i++) {
            $paths[] = $this->storage->store('unique_' . $i . '_' . uniqid(), 'full');
        }

        // All paths should be unique
        $this->assertCount(5, array_unique($paths));
    }

    public function testLargeFileStorage(): void
    {
        // Create a 1MB file
        $data = random_bytes(1024 * 1024);

        $path = $this->storage->store($data, 'full');
        $retrieved = $this->storage->retrieve($path);

        $this->assertEquals($data, $retrieved);
    }
}
