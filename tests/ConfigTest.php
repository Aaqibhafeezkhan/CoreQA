<?php
declare(strict_types=1);

return [
    'Production configuration rejects an unset CSRF key' => static function (): void {
        $previousEnvironment = getenv('APP_ENV');
        $previousKey = getenv('CSRF_KEY');
        putenv('APP_ENV=production');
        putenv('CSRF_KEY');
        assertThrows(static fn() => require __DIR__ . '/../config/config.php', RuntimeException::class);
        $previousEnvironment === false ? putenv('APP_ENV') : putenv('APP_ENV=' . $previousEnvironment);
        $previousKey === false ? putenv('CSRF_KEY') : putenv('CSRF_KEY=' . $previousKey);
    },
    'Production configuration rejects the example placeholder' => static function (): void {
        $previousEnvironment = getenv('APP_ENV');
        $previousKey = getenv('CSRF_KEY');
        putenv('APP_ENV=production');
        putenv('CSRF_KEY=replace-with-a-random-64-character-secret');
        assertThrows(static fn() => require __DIR__ . '/../config/config.php', RuntimeException::class);
        $previousEnvironment === false ? putenv('APP_ENV') : putenv('APP_ENV=' . $previousEnvironment);
        $previousKey === false ? putenv('CSRF_KEY') : putenv('CSRF_KEY=' . $previousKey);
    },
];
