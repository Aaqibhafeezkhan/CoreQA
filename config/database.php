<?php
declare(strict_types=1);

use App\Core\DatabaseUrl;

return static function (array $config): \PDO {
    $databaseUrl = trim((string)($config['db']['url'] ?? ''));
    $isVercel = !empty($_SERVER['VERCEL_DEPLOYMENT']) || (getenv('VERCEL') !== false && getenv('VERCEL') !== '');

    if ($databaseUrl !== '') {
        $connection = DatabaseUrl::parse($databaseUrl);
        $dsn = $connection['dsn'];
        $user = $connection['user'];
        $password = $connection['pass'];
    } else {
        if ($isVercel) {
            throw new RuntimeException('CoreQA requires a persistent PostgreSQL database on Vercel. Connect Neon from the Vercel Marketplace to add DATABASE_URL automatically.');
        }
        $dsn = $config['db']['dsn'];
        $user = $config['db']['user'];
        $password = $config['db']['pass'];
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

    $db = new \PDO($dsn, $user, $password, $config['db']['options']);
    $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        $schema = [
            "CREATE TABLE IF NOT EXISTS users (
                id BIGSERIAL PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'mod', 'admin')),
                reputation INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS topics (
                id BIGSERIAL PRIMARY KEY,
                user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL,
                body TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'open' CHECK (status IN ('open', 'closed')),
                pinned_at TIMESTAMP NULL,
                accepted_post_id BIGINT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS posts (
                id BIGSERIAL PRIMARY KEY,
                topic_id BIGINT NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
                user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                body TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS tags (
                id BIGSERIAL PRIMARY KEY,
                name VARCHAR(80) NOT NULL,
                slug VARCHAR(80) NOT NULL UNIQUE
            )",
            "CREATE TABLE IF NOT EXISTS topic_tag (
                topic_id BIGINT NOT NULL REFERENCES topics(id) ON DELETE CASCADE,
                tag_id BIGINT NOT NULL REFERENCES tags(id) ON DELETE CASCADE,
                PRIMARY KEY (topic_id, tag_id)
            )",
            "CREATE TABLE IF NOT EXISTS votes (
                id BIGSERIAL PRIMARY KEY,
                user_id BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                post_id BIGINT NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
                value SMALLINT NOT NULL CHECK (value IN (-1, 1)),
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, post_id)
            )",
            "CREATE TABLE IF NOT EXISTS rate_limits (
                id BIGSERIAL PRIMARY KEY,
                ip BYTEA NOT NULL,
                route VARCHAR(120) NOT NULL,
                window_start TIMESTAMP NOT NULL,
                hits INTEGER NOT NULL DEFAULT 1,
                UNIQUE (ip, route, window_start)
            )",
            "CREATE TABLE IF NOT EXISTS app_sessions (
                id VARCHAR(128) PRIMARY KEY,
                payload TEXT NOT NULL,
                last_activity BIGINT NOT NULL
            )",
            'CREATE INDEX IF NOT EXISTS idx_topics_created ON topics(created_at)',
            'CREATE INDEX IF NOT EXISTS idx_posts_topic ON posts(topic_id)',
            'CREATE INDEX IF NOT EXISTS idx_posts_user ON posts(user_id)',
            'CREATE INDEX IF NOT EXISTS idx_votes_post ON votes(post_id)',
        ];

        foreach ($schema as $statement) {
            $db->exec($statement);
        }

        return $db;
    }

    if ($driver === 'sqlite') {
        $db->exec('PRAGMA foreign_keys = ON');
        $db->exec('PRAGMA journal_mode = WAL');
        $db->exec("CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'user', reputation INTEGER NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP)");
        $db->exec("CREATE TABLE IF NOT EXISTS topics (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, title TEXT NOT NULL, slug TEXT NOT NULL, body TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'open', pinned_at DATETIME, accepted_post_id INTEGER, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)");
        $db->exec('CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, topic_id INTEGER NOT NULL, user_id INTEGER NOT NULL, body TEXT NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(topic_id) REFERENCES topics(id) ON DELETE CASCADE, FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE)');
        $db->exec('CREATE TABLE IF NOT EXISTS tags (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT NOT NULL UNIQUE)');
        $db->exec('CREATE TABLE IF NOT EXISTS topic_tag (topic_id INTEGER NOT NULL, tag_id INTEGER NOT NULL, PRIMARY KEY(topic_id, tag_id), FOREIGN KEY(topic_id) REFERENCES topics(id) ON DELETE CASCADE, FOREIGN KEY(tag_id) REFERENCES tags(id) ON DELETE CASCADE)');
        $db->exec('CREATE TABLE IF NOT EXISTS votes (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, post_id INTEGER NOT NULL, value INTEGER NOT NULL CHECK(value IN (-1, 1)), created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, post_id), FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE)');
        $db->exec('CREATE TABLE IF NOT EXISTS rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT, ip BLOB NOT NULL, route TEXT NOT NULL, window_start DATETIME NOT NULL, hits INTEGER NOT NULL DEFAULT 1, UNIQUE(ip, route, window_start))');
        $db->exec('CREATE TABLE IF NOT EXISTS app_sessions (id TEXT PRIMARY KEY, payload TEXT NOT NULL, last_activity INTEGER NOT NULL)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_topics_created ON topics(created_at)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_posts_topic ON posts(topic_id)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_posts_user ON posts(user_id)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_votes_post ON votes(post_id)');

        return $db;
    }

    if ($driver === 'mysql') {
        $db->exec('CREATE TABLE IF NOT EXISTS app_sessions (id VARCHAR(128) PRIMARY KEY, payload LONGTEXT NOT NULL, last_activity BIGINT NOT NULL)');
        return $db;
    }

    throw new RuntimeException('CoreQA supports PostgreSQL, SQLite, and MySQL PDO connections.');
};
