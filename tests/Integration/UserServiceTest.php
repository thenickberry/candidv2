<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\Database;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for UserService
 *
 * Tests user CRUD operations against a real database.
 */
class UserServiceTest extends TestCase
{
    private ?Database $db = null;
    private ?UserService $userService = null;
    private array $testUserIds = [];

    protected function setUp(): void
    {
        if (!$this->isDatabaseAvailable()) {
            $this->markTestSkipped('Database not available for integration tests');
        }

        $this->db = new Database(
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_NAME'] ?? 'candid_test',
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASS'] ?? ''
        );

        $this->userService = new UserService($this->db);
    }

    protected function tearDown(): void
    {
        // Clean up test users
        foreach ($this->testUserIds as $id) {
            if ($this->db) {
                $this->db->query('DELETE FROM user WHERE id = ?', [$id]);
            }
        }
        $this->testUserIds = [];
    }

    private function isDatabaseAvailable(): bool
    {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $name = $_ENV['DB_NAME'] ?? 'candid_test';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
            new \PDO($dsn, $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    private function createTestUser(string $suffix = ''): int
    {
        $id = $this->userService->create([
            'username' => 'testuser_' . uniqid() . $suffix,
            'pword' => password_hash('password123', PASSWORD_DEFAULT),
            'email' => 'test' . uniqid() . '@example.com',
            'fname' => 'Test',
            'lname' => 'User',
            'access' => 1,
        ]);

        $this->testUserIds[] = $id;
        return $id;
    }

    public function testCreateUser(): void
    {
        $username = 'newuser_' . uniqid();
        $id = $this->userService->create([
            'username' => $username,
            'pword' => password_hash('securepassword', PASSWORD_DEFAULT),
            'email' => 'newuser@example.com',
            'fname' => 'New',
            'lname' => 'User',
            'access' => 1,
        ]);

        $this->testUserIds[] = $id;

        $this->assertGreaterThan(0, $id);

        // Verify user was created
        $user = $this->userService->find($id);
        $this->assertNotNull($user);
        $this->assertEquals($username, $user['username']);
    }

    public function testFindUserById(): void
    {
        $id = $this->createTestUser();

        $user = $this->userService->find($id);

        $this->assertNotNull($user);
        $this->assertEquals($id, $user['id']);
    }

    public function testFindReturnsNullForNonexistentId(): void
    {
        $user = $this->userService->find(999999);

        $this->assertNull($user);
    }

    public function testFindByUsername(): void
    {
        $id = $this->createTestUser();
        $createdUser = $this->userService->find($id);

        $user = $this->userService->findByUsername($createdUser['username']);

        $this->assertNotNull($user);
        $this->assertEquals($id, $user['id']);
    }

    public function testFindByUsernameReturnsNullForNonexistent(): void
    {
        $user = $this->userService->findByUsername('nonexistent_username_xyz');

        $this->assertNull($user);
    }

    public function testGetAllReturnsUsers(): void
    {
        // Create a couple test users
        $this->createTestUser('_a');
        $this->createTestUser('_b');

        $users = $this->userService->getAll();

        $this->assertIsArray($users);
        $this->assertGreaterThanOrEqual(2, count($users));
    }

    public function testUpdateUser(): void
    {
        $id = $this->createTestUser();

        $result = $this->userService->update($id, [
            'fname' => 'Updated',
            'lname' => 'Name',
            'email' => 'updated@example.com',
        ]);

        $this->assertGreaterThanOrEqual(0, $result);

        $user = $this->userService->find($id);
        $this->assertEquals('Updated', $user['fname']);
        $this->assertEquals('Name', $user['lname']);
        $this->assertEquals('updated@example.com', $user['email']);
    }

    public function testUpdateUserPassword(): void
    {
        $id = $this->createTestUser();
        $newPassword = 'newpassword456';
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $result = $this->userService->update($id, [
            'pword' => $newHash,
        ]);

        $this->assertGreaterThanOrEqual(0, $result);

        // Verify new password works
        $user = $this->db->fetchOne('SELECT pword FROM user WHERE id = ?', [$id]);
        $this->assertTrue(password_verify($newPassword, $user['pword']));
    }

    public function testDeleteUser(): void
    {
        $id = $this->createTestUser();

        // Verify user exists
        $this->assertNotNull($this->userService->find($id));

        $result = $this->userService->delete($id);

        $this->assertEquals(1, $result);

        // Verify user no longer exists
        $this->assertNull($this->userService->find($id));

        // Remove from cleanup list since already deleted
        $this->testUserIds = array_filter($this->testUserIds, fn($uid) => $uid !== $id);
    }

    public function testHashedPasswordStoredCorrectly(): void
    {
        $plainPassword = 'myplainpassword';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $id = $this->userService->create([
            'username' => 'hashtest_' . uniqid(),
            'pword' => $hashedPassword,
            'email' => 'hash@example.com',
            'access' => 1,
        ]);

        $this->testUserIds[] = $id;

        $user = $this->db->fetchOne('SELECT pword FROM user WHERE id = ?', [$id]);

        // Password hash should be stored correctly
        $this->assertEquals($hashedPassword, $user['pword']);

        // And should verify correctly
        $this->assertTrue(password_verify($plainPassword, $user['pword']));
    }
}
