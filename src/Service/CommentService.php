<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Comment Service
 *
 * Handles image comments.
 */
class CommentService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get comments for an image
     */
    public function getForImage(int $imageId): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, u.fname, u.lname, u.username
             FROM image_comment c
             JOIN user u ON c.user_id = u.id
             WHERE c.image_id = :image_id
             ORDER BY c.stamp ASC",
            ['image_id' => $imageId]
        );
    }

    /**
     * Count comments for an image
     */
    public function countForImage(int $imageId): int
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM image_comment WHERE image_id = :image_id",
            ['image_id' => $imageId]
        );
    }

    /**
     * Get recent comments by a user
     */
    public function getRecentByUser(int $userId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, i.descr as image_descr
             FROM image_comment c
             JOIN image_info i ON c.image_id = i.id
             WHERE c.user_id = :user_id
             ORDER BY c.stamp DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }

    /**
     * Add a comment
     */
    public function add(int $imageId, int $userId, string $comment): int
    {
        return $this->db->insert('image_comment', [
            'image_id' => $imageId,
            'user_id' => $userId,
            'comment' => $comment,
            'stamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete a comment
     */
    public function delete(int $id, ?array $user): bool
    {
        if (!$user) {
            return false;
        }

        $comment = $this->db->fetchOne(
            "SELECT * FROM image_comment WHERE id = :id",
            ['id' => $id]
        );

        if (!$comment) {
            return false;
        }

        // Only owner or admin can delete
        $isOwner = (int) $comment['user_id'] === (int) $user['id'];
        $isAdmin = ($user['access'] ?? 0) >= 5;

        if (!$isOwner && !$isAdmin) {
            return false;
        }

        return $this->db->delete('image_comment', 'id = :id', ['id' => $id]) > 0;
    }

    /**
     * Get image owner for notification
     */
    public function getImageOwner(int $imageId): ?array
    {
        return $this->db->fetchOne(
            "SELECT u.id, u.email, u.fname, u.lname
             FROM image_info i
             JOIN user u ON i.owner = u.id
             WHERE i.id = :image_id",
            ['image_id' => $imageId]
        );
    }
}
