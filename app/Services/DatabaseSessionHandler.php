<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use SessionHandlerInterface;
use SessionUpdateTimestampHandlerInterface;
use Throwable;

final class DatabaseSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    public function __construct(private PDO $db)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function validateId(string $id): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM app_sessions WHERE id = ? AND last_activity >= ?');
        $statement->execute([$id, time() - (int)ini_get('session.gc_maxlifetime')]);
        return (bool)$statement->fetchColumn();
    }

    public function read(string $id): string|false
    {
        $statement = $this->db->prepare('SELECT payload, last_activity FROM app_sessions WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return '';
        }

        $lifetime = (int)ini_get('session.gc_maxlifetime');
        if ((int)$row['last_activity'] < time() - $lifetime) {
            $this->destroy($id);
            return '';
        }

        $payload = base64_decode((string)$row['payload'], true);
        return $payload === false ? '' : $payload;
    }

    public function write(string $id, string $data): bool
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $payload = base64_encode($data);
        $now = time();

        try {
            if (in_array($driver, ['pgsql', 'sqlite'], true)) {
                $sql = 'INSERT INTO app_sessions (id, payload, last_activity) VALUES (?, ?, ?)
                    ON CONFLICT (id) DO UPDATE SET payload = excluded.payload, last_activity = excluded.last_activity';
            } else {
                $sql = 'INSERT INTO app_sessions (id, payload, last_activity) VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE payload = VALUES(payload), last_activity = VALUES(last_activity)';
            }

            return $this->db->prepare($sql)->execute([$id, $payload, $now]);
        } catch (Throwable $error) {
            error_log('CoreQA session persistence failed: ' . get_class($error));
            return false;
        }
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write($id, $data);
    }

    public function destroy(string $id): bool
    {
        return $this->db->prepare('DELETE FROM app_sessions WHERE id = ?')->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $statement = $this->db->prepare('DELETE FROM app_sessions WHERE last_activity < ?');
        if (!$statement->execute([time() - $max_lifetime])) {
            return false;
        }

        return $statement->rowCount();
    }
}
