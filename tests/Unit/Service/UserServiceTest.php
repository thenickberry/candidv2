<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\Database;
use App\Service\UserService;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Tests for UserService
 */
class UserServiceTest extends TestCase
{
    private UserService $service;
    private Database $db;

    protected function setUp(): void
    {
        // Create mock Database
        $this->db = $this->createMock(Database::class);
        $this->service = new UserService($this->db);
    }

    public function testGetAllReturnsUsers(): void
    {
        $expectedUsers = [
            ['id' => 1, 'username' => 'admin', 'email' => 'admin@example.com'],
            ['id' => 2, 'username' => 'user1', 'email' => 'user1@example.com'],
        ];

        $this->db->method('fetchAll')
            ->willReturn($expectedUsers);

        $users = $this->service->getAll();

        $this->assertCount(2, $users);
        $this->assertEquals('admin', $users[0]['username']);
    }

    public function testFindReturnsUserById(): void
    {
        $expectedUser = ['id' => 1, 'username' => 'admin', 'email' => 'admin@example.com'];

        $this->db->method('fetchOne')
            ->willReturn($expectedUser);

        $user = $this->service->find(1);

        $this->assertNotNull($user);
        $this->assertEquals('admin', $user['username']);
    }

    public function testFindReturnsNullForNonExistentUser(): void
    {
        $this->db->method('fetchOne')
            ->willReturn(null);

        $user = $this->service->find(999);

        $this->assertNull($user);
    }

    public function testFindByUsernameReturnsUser(): void
    {
        $expectedUser = ['id' => 1, 'username' => 'admin', 'email' => 'admin@example.com'];

        $this->db->method('fetchOne')
            ->willReturn($expectedUser);

        $user = $this->service->findByUsername('admin');

        $this->assertNotNull($user);
        $this->assertEquals(1, $user['id']);
    }

    public function testCreateReturnsNewUserId(): void
    {
        $userData = [
            'username' => 'newuser',
            'password' => 'hashedpassword',
            'email' => 'new@example.com',
            'fname' => 'New',
            'lname' => 'User',
            'access' => 1,
        ];

        $this->db->method('insert')
            ->willReturn(5);

        $id = $this->service->create($userData);

        $this->assertEquals(5, $id);
    }

    public function testUpdateReturnsAffectedRows(): void
    {
        $updateData = [
            'email' => 'updated@example.com',
            'fname' => 'Updated',
        ];

        $this->db->method('update')
            ->willReturn(1);

        $rows = $this->service->update(1, $updateData);

        $this->assertEquals(1, $rows);
    }

    public function testDeleteReturnsAffectedRows(): void
    {
        $this->db->method('delete')
            ->willReturn(1);

        $rows = $this->service->delete(1);

        $this->assertEquals(1, $rows);
    }
}
