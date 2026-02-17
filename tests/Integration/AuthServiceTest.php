<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\Auth;
use App\Service\Database;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Auth service
 *
 * Tests authentication against a real database.
 */
class AuthServiceTest extends TestCase
{
    private ?Database $db = null;
    private ?Auth $auth = null;
    private int $testUserId = 0;

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

        $this->auth = new Auth($this->db, 'test_session', 3600);

        // Create a test user
        $this->createTestUser();

        // Start session for tests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        // Clean up test user
        if ($this->testUserId > 0 && $this->db) {
            $this->db->query('DELETE FROM user WHERE id = ?', [$this->testUserId]);
        }

        // Clear session
        $_SESSION = [];
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

    private function createTestUser(): void
    {
        $username = 'test_user_' . uniqid();
        $password = password_hash('testpassword123', PASSWORD_DEFAULT);

        $this->testUserId = $this->db->insert('user', [
            'username' => $username,
            'pword' => $password,
            'fname' => 'Test',
            'lname' => 'User',
            'email' => 'test@example.com',
            'access' => 1,
            'created' => date('Y-m-d H:i:s'),
        ]);
    }

    public function testAttemptWithValidCredentials(): void
    {
        // Get the test user's username
        $user = $this->db->fetchOne('SELECT username FROM user WHERE id = ?', [$this->testUserId]);

        $result = $this->auth->attempt($user['username'], 'testpassword123');

        $this->assertTrue($result);
        $this->assertTrue($this->auth->check());
    }

    public function testAttemptWithInvalidPassword(): void
    {
        $user = $this->db->fetchOne('SELECT username FROM user WHERE id = ?', [$this->testUserId]);

        $result = $this->auth->attempt($user['username'], 'wrongpassword');

        $this->assertFalse($result);
        $this->assertFalse($this->auth->check());
    }

    public function testAttemptWithNonexistentUser(): void
    {
        $result = $this->auth->attempt('nonexistent_user_xyz', 'anypassword');

        $this->assertFalse($result);
    }

    public function testLogout(): void
    {
        $user = $this->db->fetchOne('SELECT username FROM user WHERE id = ?', [$this->testUserId]);
        $this->auth->attempt($user['username'], 'testpassword123');

        $this->assertTrue($this->auth->check());

        $this->auth->logout();

        $this->assertFalse($this->auth->check());
    }

    public function testGetUserReturnsNullWhenNotLoggedIn(): void
    {
        $this->assertNull($this->auth->getUser());
    }

    public function testGetUserReturnsDataWhenLoggedIn(): void
    {
        $user = $this->db->fetchOne('SELECT username FROM user WHERE id = ?', [$this->testUserId]);
        $this->auth->attempt($user['username'], 'testpassword123');

        $loggedInUser = $this->auth->getUser();

        $this->assertIsArray($loggedInUser);
        $this->assertEquals($user['username'], $loggedInUser['username']);
    }

    public function testHashPasswordCreatesValidHash(): void
    {
        $password = 'mysecurepassword';
        $hash = $this->auth->hashPassword($password);

        $this->assertTrue(password_verify($password, $hash));
    }

    public function testIsAdminReturnsFalseForRegularUser(): void
    {
        $user = $this->db->fetchOne('SELECT username FROM user WHERE id = ?', [$this->testUserId]);
        $this->auth->attempt($user['username'], 'testpassword123');

        $this->assertFalse($this->auth->isAdmin());
    }

    public function testIsAdminReturnsTrueForAdminUser(): void
    {
        // Update test user to admin
        $this->db->query('UPDATE user SET access = 5 WHERE id = ?', [$this->testUserId]);

        $user = $this->db->fetchOne('SELECT username FROM user WHERE id = ?', [$this->testUserId]);
        $this->auth->attempt($user['username'], 'testpassword123');

        $this->assertTrue($this->auth->isAdmin());
    }
}
