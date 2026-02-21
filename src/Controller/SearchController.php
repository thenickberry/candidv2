<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Search Controller
 */
class SearchController extends Controller
{
    public function index(): string
    {
        // Get categories for dropdown
        $categories = $this->db()->fetchAll(
            "SELECT id, name FROM category WHERE deleted_at IS NULL ORDER BY name"
        );

        // Get photographers for dropdown
        $photographers = $this->db()->fetchAll(
            "SELECT DISTINCT u.id, u.fname, u.lname
             FROM user u
             JOIN image_info i ON u.id = i.photographer
             WHERE i.deleted_at IS NULL
             ORDER BY u.lname, u.fname"
        );

        // Get people who are tagged in images
        $taggedPeople = $this->db()->fetchAll(
            "SELECT DISTINCT u.id, u.fname, u.lname
             FROM user u
             JOIN people p ON u.id = p.user_id
             JOIN image_info i ON p.image_id = i.id
             WHERE i.deleted_at IS NULL
             ORDER BY u.lname, u.fname"
        );

        return $this->view('search/index', [
            'title' => 'Search',
            'categories' => $categories,
            'photographers' => $photographers,
            'taggedPeople' => $taggedPeople,
        ]);
    }

    /**
     * Get search options as JSON for modal
     */
    public function optionsJson(): void
    {
        header('Content-Type: application/json');

        // Get categories for dropdown
        $categories = $this->db()->fetchAll(
            "SELECT id, name FROM category WHERE deleted_at IS NULL ORDER BY name"
        );

        // Get photographers for dropdown
        $photographers = $this->db()->fetchAll(
            "SELECT DISTINCT u.id, u.fname, u.lname
             FROM user u
             JOIN image_info i ON u.id = i.photographer
             WHERE i.deleted_at IS NULL
             ORDER BY u.lname, u.fname"
        );

        // Get people who are tagged in images
        $taggedPeople = $this->db()->fetchAll(
            "SELECT DISTINCT u.id, u.fname, u.lname
             FROM user u
             JOIN people p ON u.id = p.user_id
             JOIN image_info i ON p.image_id = i.id
             WHERE i.deleted_at IS NULL
             ORDER BY u.lname, u.fname"
        );

        echo json_encode([
            'categories' => $categories,
            'photographers' => $photographers,
            'taggedPeople' => $taggedPeople,
        ]);
    }

    public function results(): string
    {
        $userAccess = $this->user()['access'] ?? 0;
        $userId = $this->user()['id'] ?? 0;

        // Build query conditions
        $conditions = ["i.deleted_at IS NULL", "(i.private = 0 OR i.owner = :user_id)", "i.access <= :access"];
        $params = ['user_id' => $userId, 'access' => $userAccess];

        // Keyword search
        if ($keywords = trim($this->query('keywords', ''))) {
            $conditions[] = "i.descr LIKE :keywords";
            $params['keywords'] = '%' . $keywords . '%';
        }

        // Date range
        if ($startDate = $this->query('start_date')) {
            $conditions[] = "i.date_taken >= :start_date";
            $params['start_date'] = $startDate;
        }

        if ($endDate = $this->query('end_date')) {
            $conditions[] = "i.date_taken <= :end_date";
            $params['end_date'] = $endDate;
        }

        // Photographer
        if ($photographer = $this->query('photographer')) {
            $conditions[] = "i.photographer = :photographer";
            $params['photographer'] = (int) $photographer;
        }

        // Category
        if ($categoryId = $this->query('category_id')) {
            $conditions[] = "EXISTS (SELECT 1 FROM image_category ic WHERE ic.image_id = i.id AND ic.category_id = :category_id)";
            $params['category_id'] = (int) $categoryId;
        }

        // Tagged people (multiple) - AND logic: all selected people must be in the image
        $personIds = $_GET['person_id'] ?? [];
        if (!empty($personIds) && is_array($personIds)) {
            $personIds = array_filter(array_map('intval', $personIds));
            foreach ($personIds as $idx => $pid) {
                $key = 'person_id_' . $idx;
                $params[$key] = $pid;
                $conditions[] = "EXISTS (SELECT 1 FROM people p WHERE p.image_id = i.id AND p.user_id = :" . $key . ")";
            }
        }

        // Sort
        $sortOptions = [
            'date_taken' => 'i.date_taken DESC',
            'added' => 'i.added DESC',
            'views' => 'i.views DESC',
            'random' => 'RAND()',
        ];
        $sort = $sortOptions[$this->query('sort', 'date_taken')] ?? 'i.date_taken DESC';

        $sql = "SELECT i.id, i.descr, i.date_taken, i.views, i.width, i.height,
                       i.owner, i.camera, u.fname, u.lname,
                       c.id AS category_id, c.name AS category_name
                FROM image_info i
                LEFT JOIN user u ON i.photographer = u.id
                LEFT JOIN image_category ic ON i.id = ic.image_id AND ic.pri = 'y'
                LEFT JOIN category c ON ic.category_id = c.id
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY {$sort}";

        $images = $this->db()->fetchAll($sql, $params);

        return $this->view('search/results', [
            'title' => 'Search Results',
            'images' => $images,
            'count' => count($images),
            'query' => $_GET,
            'currentUserId' => $this->user()['id'] ?? 0,
            'isAdmin' => $this->auth()->isAdmin(),
        ]);
    }
}
