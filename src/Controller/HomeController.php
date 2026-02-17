<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Home Controller
 */
class HomeController extends Controller
{
    public function index(): string
    {
        // Get recent images for homepage
        $images = $this->db()->fetchAll(
            "SELECT i.id, i.descr, i.date_taken, i.views, i.owner, i.camera,
                    u.fname, u.lname
             FROM image_info i
             LEFT JOIN user u ON i.photographer = u.id
             WHERE i.private = 0 AND i.access <= :access AND i.deleted_at IS NULL
             ORDER BY i.added DESC
             LIMIT 15",
            ['access' => $this->user()['access'] ?? 0]
        );

        return $this->view('home/index', [
            'title' => 'Welcome to CANDIDv2',
            'images' => $images,
            'currentUserId' => $this->user()['id'] ?? 0,
            'isAdmin' => $this->auth()->isAdmin(),
        ]);
    }
}
