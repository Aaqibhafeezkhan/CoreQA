<?php
declare(strict_types=1);

use App\Services\DatabaseSessionHandler;

return [
    'Database session handler stores and restores session payloads' => static function (): void {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE app_sessions (id TEXT PRIMARY KEY, payload TEXT NOT NULL, last_activity INTEGER NOT NULL)');
        $handler = new DatabaseSessionHandler($db);

        assertTrue($handler->open('', 'coreqa_sid'));
        assertTrue($handler->write('session-one', 'user|i:42;'));
        assertTrue($handler->validateId('session-one'));
        assertSame('user|i:42;', $handler->read('session-one'));
        assertTrue($handler->updateTimestamp('session-one', 'user|i:42;'));
        assertTrue($handler->destroy('session-one'));
        assertSame('', $handler->read('session-one'));
        assertTrue($handler->close());
    },
    'Database session handler removes expired sessions' => static function (): void {
        $db = new PDO('sqlite::memory:');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE app_sessions (id TEXT PRIMARY KEY, payload TEXT NOT NULL, last_activity INTEGER NOT NULL)');
        $db->prepare('INSERT INTO app_sessions (id, payload, last_activity) VALUES (?, ?, ?)')->execute(['expired', base64_encode('old'), time() - 5000]);
        $handler = new DatabaseSessionHandler($db);

        assertSame('', $handler->read('expired'));
        assertSame(0, (int)$db->query("SELECT COUNT(*) FROM app_sessions WHERE id = 'expired'")->fetchColumn());
    },
];
