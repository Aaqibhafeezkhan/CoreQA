<?php
declare(strict_types=1);

use App\Models\Post;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Models\Vote;
use App\Services\DatabaseSessionHandler;
use App\Services\RateLimiter;

return [
    'PostgreSQL bootstrap, model workflows, rate limits, and sessions run' => static function (): void {
        $dsn = getenv('COREQA_TEST_PG_DSN') ?: '';
        if ($dsn === '') {
            return;
        }

        $config = [
            'db' => [
                'url' => '',
                'dsn' => $dsn,
                'user' => getenv('COREQA_TEST_PG_USER') ?: '',
                'pass' => getenv('COREQA_TEST_PG_PASS') ?: '',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            ],
        ];
        $db = (require __DIR__ . '/../config/database.php')($config);
        $suffix = bin2hex(random_bytes(6));
        $email = 'coreqa-' . $suffix . '@example.test';
        $userId = User::create($db, 'PostgreSQL Test', $email, 'correct horse battery');
        $tagId = Tag::create($db, 'PHP ' . $suffix, 'php-' . $suffix);
        $topicId = Topic::create($db, $userId, 'PostgreSQL CoreQA ' . $suffix, 'postgres-coreqa-' . $suffix, 'Testing persistent PostgreSQL application storage.', [$tagId]);
        $postId = Post::create($db, $topicId, $userId, 'The PostgreSQL runtime handles persistent records.');

        Vote::cast($db, $postId, $userId, 1);
        Vote::cast($db, $postId, $userId, -1);

        assertTrue($userId > 0);
        assertTrue($topicId > 0);
        assertTrue($postId > 0);
        assertSame([$tagId], Tag::forTopic($db, $topicId));
        assertSame(-1, Post::score($db, $postId));
        assertSame(1, Topic::countAll($db, 'POSTGRESQL COREQA ' . $suffix, null));
        assertSame(1, count(Topic::paginated($db, 'postgresql coreqa ' . $suffix, null)));
        assertSame(1, count(Post::recentWithScore($db, 10)));

        $limiter = new RateLimiter($db, static fn(): int => 1700000000);
        assertTrue($limiter->hit('integration-' . $suffix, '127.0.0.1', 2, 60));
        assertTrue($limiter->hit('integration-' . $suffix, '127.0.0.1', 2, 60));
        assertSame(false, $limiter->hit('integration-' . $suffix, '127.0.0.1', 2, 60));

        $sessionHandler = new DatabaseSessionHandler($db);
        assertTrue($sessionHandler->write('integration-' . $suffix, 'user|i:42;'));
        assertTrue($sessionHandler->validateId('integration-' . $suffix));
        assertSame('user|i:42;', $sessionHandler->read('integration-' . $suffix));
        assertTrue($sessionHandler->destroy('integration-' . $suffix));

        Topic::delete($db, $topicId);
        assertSame(null, Topic::find($db, $topicId));
        assertSame(0, Post::score($db, $postId));
        Tag::delete($db, $tagId);
        User::delete($db, $userId);
    },
];
