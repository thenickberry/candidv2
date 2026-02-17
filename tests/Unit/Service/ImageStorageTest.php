<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\ImageStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ImageStorage service
 */
class ImageStorageTest extends TestCase
{
    private string $testStoragePath;
    private ImageStorage $storage;

    protected function setUp(): void
    {
        $this->testStoragePath = sys_get_temp_dir() . '/candidv2_test_' . uniqid();
        mkdir($this->testStoragePath, 0755, true);
        $this->storage = new ImageStorage($this->testStoragePath);
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        $this->recursiveDelete($this->testStoragePath);
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testStoreCreatesFile(): void
    {
        $data = 'test image data';

        $path = $this->storage->store($data, 'full');

        $this->assertNotEmpty($path);
        $this->assertFileExists($this->testStoragePath . '/' . $path);
    }

    public function testStoreCreatesHashBasedPath(): void
    {
        $data = 'test image data';

        $path = $this->storage->store($data, 'full');

        // Path format: XX/XX/hash.jpg (2 levels of sharding + hash + .jpg extension)
        $this->assertMatchesRegularExpression('/^[a-f0-9]{2}\/[a-f0-9]{2}\/[a-f0-9]+\.jpg$/', $path);
    }

    public function testStoreDifferentTypesCreateDifferentPaths(): void
    {
        $data = 'test image data';

        $fullPath = $this->storage->store($data, 'full');
        $thumbPath = $this->storage->store($data, 'thumb');

        $this->assertStringEndsWith('.jpg', $fullPath);
        $this->assertStringEndsWith('_thumb.jpg', $thumbPath);
    }

    public function testRetrieveReturnsStoredData(): void
    {
        $data = 'test image data for retrieval';
        $path = $this->storage->store($data, 'full');

        $retrieved = $this->storage->retrieve($path);

        $this->assertEquals($data, $retrieved);
    }

    public function testRetrieveReturnsNullForMissingFile(): void
    {
        $result = $this->storage->retrieve('nonexistent/path/full');

        $this->assertNull($result);
    }

    public function testDeleteRemovesFile(): void
    {
        $data = 'test data to delete';
        $path = $this->storage->store($data, 'full');

        $result = $this->storage->delete($path);

        $this->assertTrue($result);
        $this->assertFileDoesNotExist($this->testStoragePath . '/' . $path);
    }

    public function testDeleteReturnsFalseForMissingFile(): void
    {
        $result = $this->storage->delete('nonexistent/path/full');

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
        $this->assertFalse($this->storage->exists('nonexistent/path/full'));
    }

    public function testStoreWithBinaryData(): void
    {
        // Create some binary data (simulating image bytes)
        $binaryData = pack('C*', 0xFF, 0xD8, 0xFF, 0xE0, 0x00, 0x10);

        $path = $this->storage->store($binaryData, 'full');
        $retrieved = $this->storage->retrieve($path);

        $this->assertEquals($binaryData, $retrieved);
    }
}
