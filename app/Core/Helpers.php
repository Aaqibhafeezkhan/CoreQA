<?php
declare(strict_types=1);

namespace App\Core;

function url(string $path): string
{
    $base = rtrim($_SERVER['BASE_URI'] ?? '', '/');
    return $base . $path;
}

function redirect(string $path): never
{
    header('Location: ' . $path, true, 302);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
