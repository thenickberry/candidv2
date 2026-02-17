<?php

declare(strict_types=1);

namespace App\Service;

/**
 * History Service
 *
 * Handles action history/audit logging.
 */
class HistoryService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Log an action
     */
    public function log(
        string $action,
        ?int $userId = null,
        ?string $message = null,
        ?string $tableName = null,
        ?int $tableId = null
    ): int {
        return $this->db->insert('history', [
            'user_id' => $userId,
            'action' => $action,
            'message' => $message,
            'table_name' => $tableName,
            'table_id' => $tableId,
            'ip_addr' => $_SERVER['REMOTE_ADDR'] ?? null,
            'datetime' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Get history for a specific record
     */
    public function getForRecord(string $tableName, int $tableId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT h.*, u.username, u.fname, u.lname
             FROM history h
             LEFT JOIN user u ON h.user_id = u.id
             WHERE h.table_name = :table_name AND h.table_id = :table_id
             ORDER BY h.datetime DESC
             LIMIT :limit",
            ['table_name' => $tableName, 'table_id' => $tableId, 'limit' => $limit]
        );
    }

    /**
     * Get history by user
     */
    public function getByUser(int $userId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM history
             WHERE user_id = :user_id
             ORDER BY datetime DESC
             LIMIT :limit",
            ['user_id' => $userId, 'limit' => $limit]
        );
    }

    /**
     * Get history by IP address
     */
    public function getByIp(string $ip, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT h.*, u.username
             FROM history h
             LEFT JOIN user u ON h.user_id = u.id
             WHERE h.ip_addr = :ip
             ORDER BY h.datetime DESC
             LIMIT :limit",
            ['ip' => $ip, 'limit' => $limit]
        );
    }

    /**
     * Get recent history
     */
    public function getRecent(int $limit = 100): array
    {
        return $this->db->fetchAll(
            "SELECT h.*, u.username
             FROM history h
             LEFT JOIN user u ON h.user_id = u.id
             ORDER BY h.datetime DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }

    /**
     * Clean old history entries
     */
    public function cleanup(int $daysToKeep = 90): int
    {
        return $this->db->delete(
            'history',
            'datetime < DATE_SUB(NOW(), INTERVAL :days DAY)',
            ['days' => $daysToKeep]
        );
    }
}
