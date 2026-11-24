<?php
declare(strict_types=1);

$isVercel = !empty($_SERVER['VERCEL_DEPLOYMENT']) || (getenv('VERCEL') !== false && getenv('VERCEL') !== '');
$environment = getenv('APP_ENV') ?: ($isVercel ? 'production' : 'local');
$baseUrl = rtrim(getenv('APP_BASE_URL') ?: '/', '/');
$databaseUrl = trim((string)(getenv('DATABASE_URL') ?: ''));
$databaseDsn = getenv('DB_DSN') ?: 'sqlite:' . __DIR__ . '/../storage/database.sqlite';
$providedCsrfKey = getenv('CSRF_KEY') ?: '';
$csrfPlaceholder = 'replace-with-a-random-64-character-secret';

if ($environment === 'production' && $providedCsrfKey !== ''
    && (strlen($providedCsrfKey) < 32 || hash_equals($csrfPlaceholder, $providedCsrfKey))) {
    throw new RuntimeException('Invalid application security configuration.');
}

$csrfKey = $providedCsrfKey !== ''
    ? $providedCsrfKey
    : hash_hmac('sha256', 'CoreQA CSRF signing key v1', $databaseUrl !== '' ? $databaseUrl : $databaseDsn . php_uname());

if ($environment === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

return [
    'env' => $environment,
    'base_url' => $baseUrl === '' ? '/' : $baseUrl . '/',
    'session_name' => getenv('SESSION_NAME') ?: 'coreqa_sid',
    'csrf_key' => $csrfKey,
    'hsts_enabled' => getenv('HSTS_ENABLED') !== '0',
    'cache_path' => $isVercel ? sys_get_temp_dir() . '/coreqa-cache' : __DIR__ . '/../storage/topics',
    'db' => [
        'url' => $databaseUrl,
        'dsn' => $databaseDsn,
        'user' => getenv('DB_USER') ?: '',
        'pass' => getenv('DB_PASS') ?: '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];
