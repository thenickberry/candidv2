<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\ForbiddenException;

/**
 * Authentication Service
 *
 * Handles user authentication, sessions, and access control.
 */
class Auth
{
    private Database $db;
    /** @phpstan-ignore-next-line Reserved for future remember-me cookie feature */
    private string $cookieName;
    private int $sessionLifetime;
    private ?array $user = null;
    private bool $checked = false;

    public function __construct(Database $db, string $cookieName, int $sessionLifetime)
    {
        $this->db = $db;
        $this->cookieName = $cookieName;
        $this->sessionLifetime = $sessionLifetime;
    }

    /**
     * Attempt to log in a user
     */
    public function attempt(string $username, string $password): bool
    {
        $user = $this->db->fetchOne(
            "SELECT id, username, pword, access, fname, lname, email, numrows, numcols,
                    name_disp, init_disp, theme, must_change_password
             FROM user
             WHERE username = :username",
            ['username' => $username]
        );

        if (!$user) {
            return false;
        }

        // Verify password (supports both bcrypt and legacy PASSWORD() format)
        if (!$this->verifyPassword($password, $user['pword'])) {
            return false;
        }

        // Regenerate session ID on login
        session_regenerate_id(true);

        // Create session record
        $sessionId = $this->generateSessionId();
        $expire = date('Y-m-d H:i:s', time() + $this->sessionLifetime);

        $this->db->insert('session', [
            'session_id' => $sessionId,
            'user_id' => $user['id'],
            'expire' => $expire,
        ]);

        // Set session cookie
        $_SESSION['session_id'] = $sessionId;
        $_SESSION['user_id'] = $user['id'];

        // Store user data (without password)
        unset($user['pword']);
        $this->user = $user;
        $this->checked = true;

        return true;
    }

    /**
     * Log out the current user
     */
    public function logout(): void
    {
        if (isset($_SESSION['session_id'])) {
            $this->db->delete('session', 'session_id = :session_id', [
                'session_id' => $_SESSION['session_id']
            ]);
        }

        // Clear session
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        $this->user = null;
        $this->checked = false;
    }

    /**
     * Check if user is authenticated
     */
    public function check(): bool
    {
        $this->loadUser();
        return $this->user !== null;
    }

    /**
     * Get the current authenticated user
     */
    public function getUser(): ?array
    {
        $this->loadUser();
        return $this->user;
    }

    /**
     * Get user ID
     */
    public function userId(): ?int
    {
        $user = $this->getUser();
        return $user ? (int) $user['id'] : null;
    }

    /**
     * Check if user has at least the specified access level
     */
    public function hasAccess(int $level): bool
    {
        $user = $this->getUser();
        return $user && ($user['access'] ?? 0) >= $level;
    }

    /**
     * Check if user is admin (access level 5)
     */
    public function isAdmin(): bool
    {
        return $this->hasAccess(5);
    }

    /**
     * Check if user must change their password
     */
    public function mustChangePassword(): bool
    {
        $user = $this->getUser();
        return $user && ($user['must_change_password'] ?? false);
    }

    /**
     * Clear the must_change_password flag
     */
    public function clearMustChangePassword(int $userId): void
    {
        $this->db->update('user', ['must_change_password' => 0], 'id = :id', ['id' => $userId]);

        // Update cached user data
        if ($this->user && (int)$this->user['id'] === $userId) {
            $this->user['must_change_password'] = 0;
        }
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    public function requireAuth(): void
    {
        if (!$this->check()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            redirect('/login');
        }
    }

    /**
     * Require admin access
     */
    public function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            throw new ForbiddenException();
        }
    }

    /**
     * Hash a password using bcrypt
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify a password against a hash
     */
    private function verifyPassword(string $password, string $hash): bool
    {
        // Try bcrypt first
        if (password_verify($password, $hash)) {
            return true;
        }

        // Legacy: MySQL PASSWORD() format (16 char hex)
        // This allows migration from old passwords
        if (strlen($hash) === 16 || strlen($hash) === 41) {
            // For migration, we'd need to check against MySQL PASSWORD()
            // This is a placeholder - actual migration would need database query
            return false;
        }

        return false;
    }

    /**
     * Load user from session
     */
    private function loadUser(): void
    {
        if ($this->checked) {
            return;
        }

        $this->checked = true;

        if (empty($_SESSION['session_id'])) {
            return;
        }

        $sessionId = $_SESSION['session_id'];

        // Get session with user data
        $result = $this->db->fetchOne(
            "SELECT u.id, u.username, u.access, u.fname, u.lname, u.email,
                    u.numrows, u.numcols, u.name_disp,
                    u.init_disp, u.theme, u.must_change_password, s.expire
             FROM session s
             JOIN user u ON s.user_id = u.id
             WHERE s.session_id = :session_id",
            ['session_id' => $sessionId]
        );

        if (!$result) {
            // Invalid session
            unset($_SESSION['session_id'], $_SESSION['user_id']);
            return;
        }

        // Check expiration
        if (strtotime($result['expire']) < time()) {
            // Session expired
            $this->db->delete('session', 'session_id = :session_id', ['session_id' => $sessionId]);
            unset($_SESSION['session_id'], $_SESSION['user_id']);
            return;
        }

        // Update last activity
        $this->db->update(
            'session',
            ['expire' => date('Y-m-d H:i:s', time() + $this->sessionLifetime)],
            'session_id = :session_id',
            ['session_id' => $sessionId]
        );

        unset($result['expire']);
        $this->user = $result;
    }

    /**
     * Generate a secure session ID
     */
    private function generateSessionId(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        return $this->db->delete('session', 'expire < NOW()');
    }
}
