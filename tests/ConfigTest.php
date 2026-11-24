<?php
declare(strict_types=1);

$withEnvironment = static function (array $values, callable $callback): void {
    $previous = [];
    foreach ($values as $key => $value) {
        $previous[$key] = getenv($key);
        $value === null ? putenv($key) : putenv($key . '=' . $value);
    }

    try {
        $callback();
    } finally {
        foreach ($previous as $key => $value) {
            $value === false ? putenv($key) : putenv($key . '=' . $value);
        }
    }
};

return [
    'Production configuration derives a stable CSRF key from the database URL' => static function () use ($withEnvironment): void {
        $url = 'postgresql://coreqa:local%40secret@localhost:5432/coreqa?sslmode=require';
        $withEnvironment([
            'APP_ENV' => 'production',
            'DATABASE_URL' => $url,
            'DB_DSN' => null,
            'CSRF_KEY' => null,
            'VERCEL' => null,
        ], static function () use ($url): void {
            $config = require __DIR__ . '/../config/config.php';
            assertSame('production', $config['env']);
            assertSame(hash_hmac('sha256', 'CoreQA CSRF signing key v1', $url), $config['csrf_key']);
            assertTrue(strlen($config['csrf_key']) >= 32);
        });
    },
    'Production configuration rejects an explicitly weak CSRF key' => static function () use ($withEnvironment): void {
        $withEnvironment([
            'APP_ENV' => 'production',
            'DATABASE_URL' => 'postgresql://coreqa:secret@localhost:5432/coreqa?sslmode=require',
            'CSRF_KEY' => 'short-key',
            'VERCEL' => null,
        ], static function (): void {
            assertThrows(static fn() => require __DIR__ . '/../config/config.php', RuntimeException::class);
        });
    },
    'Production configuration rejects the example CSRF placeholder' => static function () use ($withEnvironment): void {
        $withEnvironment([
            'APP_ENV' => 'production',
            'DATABASE_URL' => 'postgresql://coreqa:secret@localhost:5432/coreqa?sslmode=require',
            'CSRF_KEY' => 'replace-with-a-random-64-character-secret',
            'VERCEL' => null,
        ], static function (): void {
            assertThrows(static fn() => require __DIR__ . '/../config/config.php', RuntimeException::class);
        });
    },
];
