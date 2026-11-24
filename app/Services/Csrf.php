<?php
declare(strict_types=1);

namespace App\Services;

class Csrf
{
    public function __construct(private string $key)
    {
    }

    public function token(): string
    {
        if (!isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf']) || $_SESSION['_csrf'] === '') {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return hash_hmac('sha256', $_SESSION['_csrf'], $this->key);
    }

    public function rotate(): string
    {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return $this->token();
    }

    public function field(): string
    {
        return '<input type="hidden" name="_token" value="' . $this->token() . '">';
    }

    public function validate(mixed $token): bool
    {
        if (!is_string($token) || $token === '' || !isset($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            return false;
        }

        return hash_equals($this->token(), $token);
    }
}
