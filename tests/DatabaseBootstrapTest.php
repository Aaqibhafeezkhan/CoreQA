<?php
declare(strict_types=1);

return [
    'SQLite bootstrap creates application and session tables' => static function (): void {
        $config = [
            'db' => [
                'url' => '',
                'dsn' => 'sqlite::memory:',
                'user' => '',
                'pass' => '',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],
        ];
        $db = (require __DIR__ . '/../config/database.php')($config);
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_COLUMN);
        foreach (['users', 'topics', 'posts', 'tags', 'topic_tag', 'votes', 'rate_limits', 'app_sessions'] as $table) {
            assertTrue(in_array($table, $tables, true), 'Missing bootstrapped table: ' . $table);
        }
    },
    'Vercel database bootstrap refuses ephemeral SQLite fallback' => static function (): void {
        $previous = $_SERVER['VERCEL_DEPLOYMENT'] ?? null;
        $_SERVER['VERCEL_DEPLOYMENT'] = '1';
        $config = [
            'db' => [
                'url' => '',
                'dsn' => 'sqlite::memory:',
                'user' => '',
                'pass' => '',
                'options' => [],
            ],
        ];

        try {
            assertThrows(static fn() => (require __DIR__ . '/../config/database.php')($config), RuntimeException::class);
        } finally {
            if ($previous === null) {
                unset($_SERVER['VERCEL_DEPLOYMENT']);
            } else {
                $_SERVER['VERCEL_DEPLOYMENT'] = $previous;
            }
        }
    },
];
