<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Database;
use App\Service\ImageService;
use App\Service\ImageStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ImageService
 */
class ImageServiceTest extends TestCase
{
    private ImageService $service;
    private Database $db;
    private ImageStorage $storage;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Database::class);
        $this->storage = $this->createMock(ImageStorage::class);
        $this->service = new ImageService($this->db, $this->storage);
    }

    public function testFindReturnsImage(): void
    {
        $expected = ['id' => 1, 'descr' => 'Test Image', 'deleted_at' => null];

        $this->db->method('fetchOne')
            ->willReturn($expected);

        $image = $this->service->find(1);

        $this->assertEquals('Test Image', $image['descr']);
    }

    public function testFindReturnsNullForDeletedImage(): void
    {
        $this->db->method('fetchOne')
            ->willReturn(null);

        $image = $this->service->find(1);

        $this->assertNull($image);
    }

    public function testFindWithDeletedReturnsDeletedImage(): void
    {
        $expected = ['id' => 1, 'descr' => 'Deleted', 'deleted_at' => '2024-01-01 00:00:00'];

        $this->db->method('fetchOne')
            ->willReturn($expected);

        $image = $this->service->findWithDeleted(1);

        $this->assertEquals('Deleted', $image['descr']);
        $this->assertNotNull($image['deleted_at']);
    }

    public function testSoftDeleteSetsDeletedTimestamp(): void
    {
        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(1);

        $result = $this->service->softDelete(1, 5);

        $this->assertTrue($result);
    }

    public function testSoftDeleteReturnsFalseForMissingImage(): void
    {
        $this->db->method('update')
            ->willReturn(0);

        $result = $this->service->softDelete(999, 5);

        $this->assertFalse($result);
    }

    public function testRestoreUnsetsDeletedTimestamp(): void
    {
        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(1);

        $result = $this->service->restore(1);

        $this->assertTrue($result);
    }

    public function testRestoreReturnsFalseForNonDeletedImage(): void
    {
        $this->db->method('update')
            ->willReturn(0);

        $result = $this->service->restore(1);

        $this->assertFalse($result);
    }

    public function testHardDeleteRemovesImageAndFiles(): void
    {
        $image = [
            'id' => 1,
            'file_path' => 'ab/cd/abcdef.jpg',
            'thumb_path' => 'ab/cd/abcdef_thumb.jpg',
        ];

        $this->db->method('fetchOne')
            ->willReturn($image);

        $this->storage->expects($this->exactly(2))
            ->method('delete');

        $this->db->expects($this->once())
            ->method('delete')
            ->willReturn(1);

        $result = $this->service->hardDelete(1);

        $this->assertTrue($result);
    }

    public function testHardDeleteReturnsFalseForMissingImage(): void
    {
        $this->db->method('fetchOne')
            ->willReturn(null);

        $result = $this->service->hardDelete(999);

        $this->assertFalse($result);
    }

    public function testGetDeletedReturnsDeletedImages(): void
    {
        $deletedImages = [
            ['id' => 1, 'descr' => 'Deleted 1', 'deleted_at' => '2024-01-01'],
            ['id' => 2, 'descr' => 'Deleted 2', 'deleted_at' => '2024-01-02'],
        ];

        $this->db->method('fetchAll')
            ->willReturn($deletedImages);

        $result = $this->service->getDeleted();

        $this->assertCount(2, $result);
    }

    public function testCountDeletedReturnsCount(): void
    {
        $this->db->method('fetchColumn')
            ->willReturn(3);

        $count = $this->service->countDeleted();

        $this->assertEquals(3, $count);
    }

    public function testCanManageReturnsTrueForAdmin(): void
    {
        $user = ['id' => 1, 'access' => 5];

        $result = $this->service->canManage(1, $user);

        $this->assertTrue($result);
    }

    public function testCanManageReturnsTrueForOwner(): void
    {
        $image = ['id' => 1, 'owner' => 2, 'deleted_at' => null];
        $user = ['id' => 2, 'access' => 1];

        $this->db->method('fetchOne')
            ->willReturn($image);

        $result = $this->service->canManage(1, $user);

        $this->assertTrue($result);
    }

    public function testCanManageReturnsFalseForNonOwner(): void
    {
        $image = ['id' => 1, 'owner' => 2, 'deleted_at' => null];
        $user = ['id' => 3, 'access' => 1];

        $this->db->method('fetchOne')
            ->willReturn($image);

        $result = $this->service->canManage(1, $user);

        $this->assertFalse($result);
    }

    public function testCanManageReturnsFalseForNoUser(): void
    {
        $result = $this->service->canManage(1, null);

        $this->assertFalse($result);
    }

    public function testEmptyTrashDeletesAllDeletedImages(): void
    {
        $deletedImages = [
            ['id' => 1, 'file_path' => 'a.jpg', 'thumb_path' => 'a_t.jpg'],
            ['id' => 2, 'file_path' => 'b.jpg', 'thumb_path' => 'b_t.jpg'],
        ];

        $this->db->method('fetchAll')
            ->willReturn($deletedImages);

        $this->db->method('fetchOne')
            ->willReturnOnConsecutiveCalls(
                $deletedImages[0],
                $deletedImages[1]
            );

        $this->db->method('delete')
            ->willReturn(1);

        $count = $this->service->emptyTrash();

        $this->assertEquals(2, $count);
    }
}
