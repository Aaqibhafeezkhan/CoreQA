<?php
declare(strict_types=1);

use App\Services\RateLimiter;

return [
    'Rate limiter enforces a deterministic fixed window' => static function (): void {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT, ip BLOB NOT NULL, route TEXT NOT NULL, window_start DATETIME NOT NULL, hits INTEGER NOT NULL DEFAULT 1, UNIQUE(ip, route, window_start))');
        $now = 1700000000;
        $limiter = new RateLimiter($db, static function () use (&$now): int {
            return $now;
        });

        assertTrue($limiter->hit('login', '127.0.0.1', 2, 60));
        assertTrue($limiter->hit('login', '127.0.0.1', 2, 60));
        assertSame(false, $limiter->hit('login', '127.0.0.1', 2, 60));
        $now += 60;
        assertTrue($limiter->hit('login', '127.0.0.1', 2, 60));
    },
];
