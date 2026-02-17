<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\Database;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for Database service
 *
 * These tests require a real database connection.
 * They are skipped if the database is not available.
 */
class DatabaseTest extends TestCase
{
    private ?Database $db = null;

    protected function setUp(): void
    {
        // Skip if no database configured
        if (!$this->isDatabaseAvailable()) {
            $this->markTestSkipped('Database not available for integration tests');
        }

        $this->db = new Database(
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_NAME'] ?? 'candid_test',
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASS'] ?? ''
        );
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

    public function testConnectionIsEstablished(): void
    {
        $this->assertInstanceOf(Database::class, $this->db);
    }

    public function testGetConnectionReturnsPdo(): void
    {
        $pdo = $this->db->getConnection();

        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    public function testFetchOneReturnsResult(): void
    {
        $result = $this->db->fetchOne('SELECT 1 as test');

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['test']);
    }

    public function testFetchAllReturnsResults(): void
    {
        $result = $this->db->fetchAll('SELECT 1 as test UNION SELECT 2 as test');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testFetchOneWithParameters(): void
    {
        $result = $this->db->fetchOne('SELECT ? as value', ['hello']);

        $this->assertEquals('hello', $result['value']);
    }

    public function testTransactionCommit(): void
    {
        $pdo = $this->db->getConnection();

        $pdo->beginTransaction();
        $result = $this->db->fetchOne('SELECT 1 as test');
        $pdo->commit();

        $this->assertEquals(1, $result['test']);
    }

    public function testTransactionRollback(): void
    {
        $pdo = $this->db->getConnection();

        $pdo->beginTransaction();
        $this->db->fetchOne('SELECT 1 as test');
        $pdo->rollBack();

        // Should still be able to query after rollback
        $result = $this->db->fetchOne('SELECT 2 as test');
        $this->assertEquals(2, $result['test']);
    }
}
