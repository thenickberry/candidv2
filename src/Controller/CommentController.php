<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CommentService;

/**
 * Comment Controller
 */
class CommentController extends Controller
{
    public function add(string $imageId): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $id = (int) $imageId;
        $comment = trim($this->input('comment', ''));

        if (empty($comment)) {
            $this->flash('error', 'Comment cannot be empty.');
            $this->redirect('/image/' . $id);
        }

        $this->getCommentService()->add($id, $this->user()['id'], $comment);

        $this->flash('success', 'Comment added.');
        $this->redirect('/image/' . $id);
    }

    public function delete(string $id): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $commentId = (int) $id;
        $imageId = $this->input('image_id');

        if ($this->getCommentService()->delete($commentId, $this->user())) {
            $this->flash('success', 'Comment deleted.');
        } else {
            $this->flash('error', 'Could not delete comment.');
        }

        $this->redirect('/image/' . $imageId);
    }

    private function getCommentService(): CommentService
    {
        static $service = null;
        if ($service === null) {
            $service = new CommentService($this->db());
        }
        return $service;
    }
}
