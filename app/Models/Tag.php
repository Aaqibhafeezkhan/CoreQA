<?php

namespace App\Models;

use PDO;

class Tag
{
    public static function all(PDO $db): array
    {
        return $db->query("SELECT * FROM tags ORDER BY name")->fetchAll();
    }

    public static function forTopic(PDO $db, int $topicId): array
    {
        $stmt = $db->prepare("SELECT tag_id FROM topic_tag WHERE topic_id = :topic_id");
        $stmt->execute([':topic_id' => $topicId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function create(PDO $db, string $name, string $slug): int
    {
        $sql = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
            ? 'INSERT INTO tags(name, slug) VALUES(?, ?) RETURNING id'
            : 'INSERT INTO tags(name, slug) VALUES(?, ?)';
        $statement = $db->prepare($sql);
        $statement->execute([$name, $slug]);
        return $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
            ? (int)$statement->fetchColumn()
            : (int)$db->lastInsertId();
    }

    public static function delete(PDO $db, int $id): void
    {
        $db->prepare("DELETE FROM tags WHERE id=?")->execute([$id]);
    }
}
