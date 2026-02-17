<?php

declare(strict_types=1);

namespace App\Controller;

/**
 * Authentication Controller
 */
class AuthController extends Controller
{
    public function showLogin(): string
    {
        // Already logged in?
        if ($this->auth()->check()) {
            $this->redirect('/');
        }

        return $this->view('auth/login', [
            'title' => 'Login',
        ]);
    }

    public function login(): void
    {
        $this->validateCsrf();

        $username = trim($this->input('username', ''));
        $password = $this->input('password', '');

        if (empty($username) || empty($password)) {
            $this->flash('error', 'Please enter username and password.');
            $this->redirect('/login');
        }

        if ($this->auth()->attempt($username, $password)) {
            $this->flash('success', 'Welcome back!');

            // Redirect to intended URL or home
            $intended = $_SESSION['intended_url'] ?? '/';
            unset($_SESSION['intended_url']);
            $this->redirect($intended);
        }

        $this->flash('error', 'Invalid username or password.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        $this->auth()->logout();
        $this->flash('success', 'You have been logged out.');
        $this->redirect('/login');
    }

    public function showRegister(): string
    {
        // Registration is invite-only
        $this->flash('info', 'Registration is invite-only. Please contact an administrator.');
        $this->redirect('/login');
    }

    public function register(): void
    {
        // Registration is invite-only - redirect to login
        $this->flash('info', 'Registration is invite-only. Please contact an administrator.');
        $this->redirect('/login');
    }
}
