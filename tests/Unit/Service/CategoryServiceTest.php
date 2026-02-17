<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\CategoryService;
use App\Service\Database;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CategoryService
 */
class CategoryServiceTest extends TestCase
{
    private CategoryService $service;
    private Database $db;

    protected function setUp(): void
    {
        $this->db = $this->createMock(Database::class);
        $this->service = new CategoryService($this->db);
    }

    public function testFindReturnsCategory(): void
    {
        $expected = ['id' => 1, 'name' => 'Nature', 'descr' => 'Nature photos', 'deleted_at' => null];

        $this->db->method('fetchOne')
            ->willReturn($expected);

        $category = $this->service->find(1);

        $this->assertEquals('Nature', $category['name']);
    }

    public function testFindReturnsNullForMissing(): void
    {
        $this->db->method('fetchOne')
            ->willReturn(null);

        $category = $this->service->find(999);

        $this->assertNull($category);
    }

    public function testFindWithDeletedReturnsDeletedCategory(): void
    {
        $expected = ['id' => 1, 'name' => 'Deleted', 'deleted_at' => '2024-01-01 00:00:00'];

        $this->db->method('fetchOne')
            ->willReturn($expected);

        $category = $this->service->findWithDeleted(1);

        $this->assertEquals('Deleted', $category['name']);
        $this->assertNotNull($category['deleted_at']);
    }

    public function testGetRootCategoriesReturnsCategories(): void
    {
        $expectedCategories = [
            ['id' => 1, 'name' => 'Nature', 'parent' => null, 'image_count' => 5],
            ['id' => 2, 'name' => 'People', 'parent' => null, 'image_count' => 3],
        ];

        // First call returns root categories, subsequent calls return empty (no descendants)
        $this->db->method('fetchAll')
            ->willReturnOnConsecutiveCalls($expectedCategories, [], []);

        $this->db->method('fetchColumn')
            ->willReturn(5); // Image count

        $categories = $this->service->getRootCategories();

        $this->assertCount(2, $categories);
    }

    public function testGetChildrenReturnsSubcategories(): void
    {
        $expectedChildren = [
            ['id' => 3, 'name' => 'Flowers', 'parent' => 1, 'image_count' => 2],
            ['id' => 4, 'name' => 'Trees', 'parent' => 1, 'image_count' => 3],
        ];

        // First call returns children, subsequent calls return empty (no descendants)
        $this->db->method('fetchAll')
            ->willReturnOnConsecutiveCalls($expectedChildren, [], []);

        $this->db->method('fetchColumn')
            ->willReturn(2); // Image count

        $children = $this->service->getChildren(1);

        $this->assertCount(2, $children);
        $this->assertEquals('Flowers', $children[0]['name']);
    }

    public function testCreateReturnsNewCategoryId(): void
    {
        $this->db->method('insert')
            ->willReturn(5);
        $this->db->method('update')
            ->willReturn(1);

        $id = $this->service->create([
            'name' => 'New Category',
            'descr' => 'Description',
            'parent' => null,
            'owner' => 1,
            'public' => 'y',
        ]);

        $this->assertEquals(5, $id);
    }

    public function testUpdateReturnsTrue(): void
    {
        $this->db->method('update')
            ->willReturn(1);

        $result = $this->service->update(1, [
            'name' => 'Updated Name',
            'descr' => 'Updated description',
        ]);

        $this->assertTrue($result);
    }

    public function testDeleteReturnsTrueWhenNoChildren(): void
    {
        $this->db->method('fetchColumn')
            ->willReturn(0); // No children
        $this->db->method('delete')
            ->willReturn(1);

        $result = $this->service->delete(1);

        $this->assertTrue($result);
    }

    public function testDeleteReturnsFalseWhenHasChildren(): void
    {
        $this->db->method('fetchColumn')
            ->willReturn(2); // Has children

        $result = $this->service->delete(1);

        $this->assertFalse($result);
    }

    public function testSoftDeleteSetsDeletedTimestamp(): void
    {
        // No descendants
        $this->db->method('fetchAll')
            ->willReturnOnConsecutiveCalls([], []);

        $this->db->expects($this->atLeastOnce())
            ->method('update')
            ->willReturn(1);

        $this->db->method('beginTransaction')->willReturn(true);
        $this->db->method('commit')->willReturn(true);

        $result = $this->service->softDelete(1, 5);

        $this->assertTrue($result);
    }

    public function testRestoreUnsetDeletedTimestamp(): void
    {
        $deletedCategory = ['id' => 1, 'name' => 'Test', 'parent' => null, 'deleted_at' => '2024-01-01'];

        $this->db->method('fetchOne')
            ->willReturn($deletedCategory);

        $this->db->expects($this->once())
            ->method('update')
            ->willReturn(1);

        $this->db->method('beginTransaction')->willReturn(true);
        $this->db->method('commit')->willReturn(true);

        $result = $this->service->restore(1);

        $this->assertTrue($result);
    }

    public function testRestoreReturnsFalseForNonDeletedCategory(): void
    {
        $category = ['id' => 1, 'name' => 'Test', 'parent' => null, 'deleted_at' => null];

        $this->db->method('fetchOne')
            ->willReturn($category);

        $result = $this->service->restore(1);

        $this->assertFalse($result);
    }

    public function testHardDeletePermanentlyRemovesCategory(): void
    {
        $this->db->expects($this->once())
            ->method('delete')
            ->willReturn(1);

        $result = $this->service->hardDelete(1);

        $this->assertTrue($result);
    }

    public function testGetDeletedReturnsDeletedCategories(): void
    {
        $deletedCategories = [
            ['id' => 1, 'name' => 'Deleted 1', 'deleted_at' => '2024-01-01'],
            ['id' => 2, 'name' => 'Deleted 2', 'deleted_at' => '2024-01-02'],
        ];

        $this->db->method('fetchAll')
            ->willReturn($deletedCategories);

        $result = $this->service->getDeleted();

        $this->assertCount(2, $result);
    }

    public function testCountDeletedReturnsCount(): void
    {
        $this->db->method('fetchColumn')
            ->willReturn(5);

        $count = $this->service->countDeleted();

        $this->assertEquals(5, $count);
    }

    public function testCanEditReturnsTrueForAdmin(): void
    {
        $user = ['id' => 1, 'access' => 5]; // Admin

        $result = $this->service->canEdit(1, $user);

        $this->assertTrue($result);
    }

    public function testCanEditReturnsTrueForOwner(): void
    {
        $category = ['id' => 1, 'owner' => 2, 'deleted_at' => null];
        $user = ['id' => 2, 'access' => 1];

        $this->db->method('fetchOne')
            ->willReturn($category);

        $result = $this->service->canEdit(1, $user);

        $this->assertTrue($result);
    }

    public function testCanEditReturnsFalseForNonOwner(): void
    {
        $category = ['id' => 1, 'owner' => 2, 'deleted_at' => null];
        $user = ['id' => 3, 'access' => 1];

        $this->db->method('fetchOne')
            ->willReturn($category);

        $result = $this->service->canEdit(1, $user);

        $this->assertFalse($result);
    }

    public function testCanEditReturnsFalseForNoUser(): void
    {
        $result = $this->service->canEdit(1, null);

        $this->assertFalse($result);
    }
}
