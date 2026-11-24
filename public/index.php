<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $to): never
{
    header('Location: ' . $to, true, 302);
    exit;
}

require_once __DIR__ . '/../config/autoload.php';

$isVercel = !empty($_SERVER['VERCEL_DEPLOYMENT']) || (getenv('VERCEL') !== false && getenv('VERCEL') !== '');

try {
    $config = require __DIR__ . '/../config/config.php';
    $db = (require __DIR__ . '/../config/database.php')($config);
} catch (Throwable $error) {
    error_log('CoreQA startup failure: ' . get_class($error));
    http_response_code($isVercel ? 503 : 500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $isVercel
        ? 'CoreQA needs a persistent PostgreSQL database. Connect Neon from the Vercel Marketplace to this project, then redeploy.'
        : 'The application configuration or database could not be initialized.';
    exit;
}

if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
    session_set_save_handler(new App\Services\DatabaseSessionHandler($db), true);
}

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $config['env'] === 'production';
session_name($config['session_name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header("Content-Security-Policy: default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' https:; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self'");
if ($config['env'] === 'production' && $secure && $config['hsts_enabled']) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

try {
    $container = new App\Core\Container($config, $db);
    $router = new App\Core\Router($container);
    (require __DIR__ . '/../config/routes.php')($router);
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', is_string($path) ? $path : '/');
} catch (Throwable $error) {
    error_log('CoreQA request failure: ' . get_class($error));
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo 'An unexpected application error occurred.';
}
