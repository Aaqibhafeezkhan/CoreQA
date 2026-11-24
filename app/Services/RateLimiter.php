<?php
declare(strict_types=1);

namespace App\Services;

use Closure;
use InvalidArgumentException;
use PDO;

class RateLimiter
{
    private Closure $clock;

    public function __construct(private PDO $db, ?callable $clock = null)
    {
        $this->clock = $clock === null
            ? static fn(): int => time()
            : Closure::fromCallable($clock);
    }

    public function hit(string $route, string $ip, int $max, int $windowSec): bool
    {
        if ($max < 1 || $windowSec < 1) {
            throw new InvalidArgumentException('Rate limit and window must be positive.');
        }

        $ipBin = inet_pton($ip);
        if ($ipBin === false) {
            $ipBin = substr(hash('sha256', $ip, true), 0, 16);
        }

        $windowStart = (int)(($this->clock)() / $windowSec) * $windowSec;
        $window = date('Y-m-d H:i:s', $windowStart);
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $ipBin = '\x' . bin2hex($ipBin);
            $statement = $this->db->prepare('
                INSERT INTO rate_limits (ip, route, window_start, hits) VALUES (?, ?, ?, 1)
                ON CONFLICT (ip, route, window_start)
                DO UPDATE SET hits = rate_limits.hits + 1
                WHERE rate_limits.hits < ?
                RETURNING hits
            ');
            $statement->execute([$ipBin, $route, $window, $max]);
            return $statement->fetchColumn() !== false;
        }

        $this->db->beginTransaction();
        try {
            $select = $driver === 'sqlite'
                ? 'SELECT id, hits FROM rate_limits WHERE ip = ? AND route = ? AND window_start = ?'
                : 'SELECT id, hits FROM rate_limits WHERE ip = ? AND route = ? AND window_start = ? FOR UPDATE';
            $stmt = $this->db->prepare($select);
            $stmt->execute([$ipBin, $route, $window]);
            $row = $stmt->fetch();

            if ($row) {
                if ((int)$row['hits'] >= $max) {
                    $this->db->rollBack();
                    return false;
                }
                $this->db->prepare('UPDATE rate_limits SET hits = hits + 1 WHERE id = ?')->execute([$row['id']]);
            } else {
                $this->db->prepare('INSERT INTO rate_limits (ip, route, window_start, hits) VALUES (?, ?, ?, 1)')
                    ->execute([$ipBin, $route, $window]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
