<?php
declare(strict_types=1);

use App\Models\Post;
use App\Models\Tag;
use App\Models\Topic;
use App\Models\User;
use App\Models\Vote;

return [
    'SQLite-backed question, answer, tag, vote, and delete workflows run' => static function (): void {
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
        $userId = User::create($db, 'Test User', 'test@example.com', 'correct horse battery');
        $tagId = Tag::create($db, 'PHP', 'php');
        $topicId = Topic::create($db, $userId, 'How does CoreQA deploy?', 'coreqa-deploy', 'I need to understand the database setup.', [$tagId]);
        $postId = Post::create($db, $topicId, $userId, 'The app initializes its schema automatically.');
        Vote::cast($db, $postId, $userId, 1);
        Vote::cast($db, $postId, $userId, -1);

        assertTrue($userId > 0);
        assertTrue($topicId > 0);
        assertTrue($postId > 0);
        assertSame([$tagId], Tag::forTopic($db, $topicId));
        assertSame(-1, Post::score($db, $postId));
        assertSame(1, Topic::countAll($db, 'CoreQA', null));
        assertSame(1, count(Topic::paginated($db, 'CoreQA', null)));

        Topic::delete($db, $topicId);
        assertSame(null, Topic::find($db, $topicId));
        assertSame(0, Post::score($db, $postId));
    },
];
