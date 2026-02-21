<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Profile Service
 *
 * Handles user profile operations.
 */
class ProfileService
{
    private Database $db;
    private Auth $auth;

    public function __construct(Database $db, Auth $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    /**
     * Get user by ID
     */
    public function find(int $id): ?array
    {
        $user = $this->db->fetchOne(
            "SELECT id, username, fname, lname, email, access, numrows, numcols,
                    name_disp, init_disp, theme, created
             FROM user
             WHERE id = :id",
            ['id' => $id]
        );

        return $user ?: null;
    }

    /**
     * Get user by username
     */
    public function findByUsername(string $username): ?array
    {
        $user = $this->db->fetchOne(
            "SELECT id, username, fname, lname, email, access, created
             FROM user
             WHERE username = :username",
            ['username' => $username]
        );

        return $user ?: null;
    }

    /**
     * Get all users (for dropdowns, people tags)
     */
    public function getAllUsers(bool $activeOnly = true): array
    {
        $sql = "SELECT id, username, fname, lname, email
                FROM user
                WHERE onlist = 'y'
                ORDER BY lname, fname";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get display name for a user
     */
    public function getDisplayName(array $user, ?string $format = null): string
    {
        $format = $format ?? ($user['name_disp'] ?? 'fname');

        return match ($format) {
            'lname' => $user['lname'] ?? $user['username'],
            'both' => trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')) ?: $user['username'],
            default => $user['fname'] ?? $user['username'],
        };
    }

    /**
     * Update user profile
     */
    public function update(int $id, array $data): bool
    {
        $updateData = [];

        // Only update allowed fields
        $allowedFields = ['fname', 'lname', 'email', 'name_disp', 'init_disp', 'theme'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        $updateData['modified'] = date('Y-m-d H:i:s');

        return $this->db->update('user', $updateData, 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Change user password
     */
    public function changePassword(int $id, string $currentPassword, string $newPassword): bool
    {
        // Verify current password
        $user = $this->db->fetchOne(
            "SELECT pword FROM user WHERE id = :id",
            ['id' => $id]
        );

        if (!$user || !password_verify($currentPassword, $user['pword'])) {
            return false;
        }

        // Update password
        return $this->db->update(
            'user',
            [
                'pword' => $this->auth->hashPassword($newPassword),
                'modified' => date('Y-m-d H:i:s'),
            ],
            'id = :id',
            ['id' => $id]
        ) > 0;
    }

    /**
     * Get images by user
     */
    public function getImagesByUser(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT i.id, i.descr, i.date_taken, i.added, i.owner, i.camera,
                    u.fname, u.lname
             FROM image_info i
             LEFT JOIN user u ON i.photographer = u.id
             WHERE i.owner = :user_id
             ORDER BY i.added DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }

    /**
     * Get images where user is tagged
     */
    public function getTaggedImages(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT i.id, i.descr, i.date_taken, i.owner, i.camera,
                    u.fname, u.lname
             FROM image_info i
             JOIN people p ON i.id = p.image_id
             LEFT JOIN user u ON i.photographer = u.id
             WHERE p.user_id = :user_id
             ORDER BY i.date_taken DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }

    /**
     * Get user statistics
     */
    public function getStats(int $userId): array
    {
        $imageCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM image_info WHERE owner = :id",
            ['id' => $userId]
        );

        $commentCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM image_comment WHERE user_id = :id",
            ['id' => $userId]
        );

        $taggedCount = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM people WHERE user_id = :id",
            ['id' => $userId]
        );

        return [
            'images' => (int) $imageCount,
            'comments' => (int) $commentCount,
            'tagged_in' => (int) $taggedCount,
        ];
    }
}
