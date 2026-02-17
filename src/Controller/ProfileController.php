<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ProfileService;

/**
 * Profile Controller
 */
class ProfileController extends Controller
{
    public function show(string $id): string
    {
        $userId = (int) $id;
        $profile = $this->getProfileService()->find($userId);

        if (!$profile) {
            http_response_code(404);
            return $this->view('errors/404');
        }

        $stats = $this->getProfileService()->getStats($userId);
        $recentImages = $this->getProfileService()->getImagesByUser($userId, 12);
        $taggedImages = $this->getProfileService()->getTaggedImages($userId, 12);

        $displayName = $this->getProfileService()->getDisplayName($profile);

        return $this->view('profile/view', [
            'title' => $displayName . "'s Profile",
            'profile' => $profile,
            'displayName' => $displayName,
            'stats' => $stats,
            'recentImages' => $recentImages,
            'taggedImages' => $taggedImages,
            'canEdit' => $this->canEditProfile($userId),
            'currentUserId' => $this->user()['id'] ?? 0,
            'isAdmin' => $this->auth()->isAdmin(),
        ]);
    }

    public function showEdit(): string
    {
        $this->requireAuth();

        $profile = $this->getProfileService()->find($this->user()['id']);

        return $this->view('profile/edit', [
            'title' => 'Edit Profile',
            'profile' => $profile,
        ]);
    }

    public function edit(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $this->getProfileService()->update($this->user()['id'], [
            'fname' => $this->input('fname', ''),
            'lname' => $this->input('lname', ''),
            'email' => $this->input('email', ''),
            'name_disp' => $this->input('name_disp', 'fname'),
            'init_disp' => $this->input('init_disp', '480x360'),
            'theme' => $this->input('theme', 'default'),
        ]);

        $this->flash('success', 'Profile updated.');
        $this->redirect('/profile/' . $this->user()['id']);
    }

    public function showChangePassword(): string
    {
        $this->requireAuth();

        return $this->view('profile/password', [
            'title' => 'Change Password',
        ]);
    }

    public function changePassword(): void
    {
        $this->requireAuth();
        $this->validateCsrf();

        $current = $this->input('current_password', '');
        $new = $this->input('new_password', '');
        $confirm = $this->input('confirm_password', '');

        if (empty($current) || empty($new)) {
            $this->flash('error', 'All fields are required.');
            $this->redirect('/profile/password');
        }

        if ($new !== $confirm) {
            $this->flash('error', 'New passwords do not match.');
            $this->redirect('/profile/password');
        }

        if (strlen($new) < 6) {
            $this->flash('error', 'Password must be at least 6 characters.');
            $this->redirect('/profile/password');
        }

        if ($this->getProfileService()->changePassword($this->user()['id'], $current, $new)) {
            // Clear the must_change_password flag
            $this->auth()->clearMustChangePassword($this->user()['id']);

            $this->flash('success', 'Password changed.');
            $this->redirect('/profile/' . $this->user()['id']);
        } else {
            $this->flash('error', 'Current password is incorrect.');
            $this->redirect('/profile/password');
        }
    }

    private function canEditProfile(int $userId): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        // Own profile or admin
        return (int) $user['id'] === $userId || ($user['access'] ?? 0) >= 5;
    }

    private function getProfileService(): ProfileService
    {
        static $service = null;
        if ($service === null) {
            $service = new ProfileService($this->db(), $this->auth());
        }
        return $service;
    }
}
