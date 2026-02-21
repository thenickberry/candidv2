<?php

declare(strict_types=1);

namespace App\Service;

/**
 * User Service
 *
 * Handles user management operations.
 */
class UserService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all users
     */
    public function getAll(): array
    {
        return $this->db->fetchAll(
            "SELECT id, username, email, fname, lname, access, must_change_password, created
             FROM user
             ORDER BY username"
        );
    }

    /**
     * Find user by ID
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, username, email, fname, lname, access, must_change_password, created
             FROM user WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, username FROM user WHERE username = :username",
            ['username' => $username]
        );
    }

    /**
     * Create a new user
     */
    public function create(array $data): int
    {
        $data['created'] = date('Y-m-d H:i:s');
        return $this->db->insert('user', $data);
    }

    /**
     * Update a user
     */
    public function update(int $id, array $data): int
    {
        return $this->db->update('user', $data, 'id = :id', ['id' => $id]);
    }

    /**
     * Delete a user
     */
    public function delete(int $id): int
    {
        return $this->db->delete('user', 'id = :id', ['id' => $id]);
    }
}
