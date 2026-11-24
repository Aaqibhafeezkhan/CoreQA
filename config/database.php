<?php
declare(strict_types=1);

return static function (array $config): PDO {
    $dsn = $config['db']['dsn'];
    if (str_starts_with($dsn, 'sqlite:') && getenv('VERCEL')) {
        $dsn = 'sqlite:/tmp/database.sqlite';
    }

    if (str_starts_with($dsn, 'sqlite:')) {
        $path = substr($dsn, 7);
        if ($path !== ':memory:' && !str_starts_with($path, 'file:')) {
            if (!str_starts_with($path, DIRECTORY_SEPARATOR)) {
                $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . $path;
            }
            $directory = dirname($path);
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('Unable to prepare the database directory.');
            }
            $dsn = 'sqlite:' . $path;
        }
    }

    $db = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $config['db']['options']);
    if (!str_starts_with($dsn, 'sqlite:')) {
        return $db;
    }

    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'user', reputation INTEGER NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
    $db->exec("CREATE TABLE IF NOT EXISTS topics (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL, slug TEXT NOT NULL, body TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'open', pinned_at DATETIME, accepted_post_id INTEGER, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)");
    $db->exec('CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER NOT NULL, user_id INTEGER NOT NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(topic_id) REFERENCES topics(id) ON DELETE CASCADE, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
    $db->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT NOT NULL UNIQUE)');
    $db->exec('CREATE TABLE IF NOT EXISTS topic_tag (topic_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(topic_id, tag_id), FOREIGN KEY(topic_id) REFERENCES topics(id) ON DELETE CASCADE, FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE)');
    $db->exec('CREATE TABLE IF NOT EXISTS votes (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, post_id INTEGER NOT NULL, value INTEGER NOT NULL CHECK(value IN (-1, 1)), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, post_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE)');
    $db->exec('CREATE TABLE IF NOT EXISTS rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT, ip BLOB NOT NULL, route TEXT NOT NULL, window_start DATETIME NOT NULL, hits INTEGER NOT NULL DEFAULT 1, UNIQUE(ip, route, window_start))');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_topics_created ON topics(created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_posts_topic ON posts(topic_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_posts_user ON posts(user_id)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_votes_post ON votes(post_id)');

    return $db;
};
