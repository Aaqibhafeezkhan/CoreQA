<?php

namespace App\Models;

use PDO;

class Vote
{
    public static function cast(PDO $db, int $postId, int $userId, int $val): void
    {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $sql = match ($driver) {
            'sqlite', 'pgsql' => "INSERT INTO votes(post_id, user_id, value, created_at) VALUES(?, ?, ?, CURRENT_TIMESTAMP)
                ON CONFLICT(user_id, post_id) DO UPDATE SET value=excluded.value, created_at=CURRENT_TIMESTAMP",
            default => "INSERT INTO votes(post_id, user_id, value, created_at) VALUES(?, ?, ?, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE value=VALUES(value), created_at=CURRENT_TIMESTAMP",
        };
        $db->prepare($sql)->execute([$postId, $userId, $val]);
    }
}
