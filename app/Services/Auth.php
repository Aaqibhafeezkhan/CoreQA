<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Container;
use App\Models\User;

class Auth
{
    public function __construct(private Container $c)
    {
    }

    public function user(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public function id(): ?int
    {
        return isset($this->user()['id']) ? (int)$this->user()['id'] : null;
    }

    public function role(): ?string
    {
        $role = $this->user()['role'] ?? null;
        return is_string($role) ? $role : null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function can(string ...$roles): bool
    {
        return $this->check() && in_array($this->role(), $roles, true);
    }

    public function requireLogin(): void
    {
        if (!$this->check()) {
            header('Location: ' . $this->c->get('config')['base_url'] . 'login');
            exit;
        }
    }

    public function attempt(string $email, string $password): bool
    {
        $db = $this->c->get('db');
        $user = User::byEmail($db, $email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword($db, (int)$user['id'], $password);
        }

        session_regenerate_id(true);
        $this->c->get('csrf')->rotate();
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        return true;
    }

    public function refreshUser(): void
    {
        $id = $this->id();
        if ($id === null) {
            return;
        }

        $user = User::findById($this->c->get('db'), $id);
        if ($user === null) {
            $this->logout();
            return;
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool)$params['secure'],
                'httponly' => (bool)$params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }

    public function requireRole(string ...$roles): void
    {
        $this->requireLogin();
        if (!$this->can(...$roles)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
