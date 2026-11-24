<?php
declare(strict_types=1);

$environment = getenv('APP_ENV') ?: 'local';
$baseUrl = rtrim(getenv('APP_BASE_URL') ?: '/', '/');
$csrfKey = getenv('CSRF_KEY') ?: '';
$csrfPlaceholder = 'replace-with-a-random-64-character-secret';

if ($environment === 'production' && (strlen($csrfKey) < 32 || hash_equals($csrfPlaceholder, $csrfKey))) {
    throw new RuntimeException('Invalid application security configuration.');
}

if ($environment === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

return [
    'env' => $environment,
    'base_url' => $baseUrl === '' ? '/' : $baseUrl . '/',
    'session_name' => getenv('SESSION_NAME') ?: 'coreqa_sid',
    'csrf_key' => $csrfKey !== '' ? $csrfKey : hash('sha256', __FILE__ . php_uname()),
    'hsts_enabled' => getenv('HSTS_ENABLED') !== '0',
    'db' => [
        'dsn' => getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/../storage/database.sqlite',
        'user' => getenv('DB_USER') ?: '',
        'pass' => getenv('DB_PASS') ?: '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];
