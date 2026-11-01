<?php

namespace App\Controllers;

use App\Core\View;
use App\Models\{Topic, Tag, Post};

class TopicController
{
    public function __construct(private \App\Core\Container $c) {}

    public function home()
    {
        $db = $this->c->get('db');
        $q = $_GET['search'] ?? null;
        $tag = $_GET['tag'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 10;

        $topics = Topic::paginated($db, $q, $tag, $page, $per);
        $total = Topic::countAll($db, $q, $tag);
        $pages = max(1, (int)ceil($total / $per));

        View::render('home', [
            'title' => 'CoreQA',
            'topics' => $topics,
            'tags' => Tag::all($db),
            'auth' => $this->c->get('auth'),
            'csrf' => $this->c->get('csrf'),
            'page' => $page,
            'pages' => $pages,
            'q' => $q,
            'tag' => $tag
        ]);
    }

    public function index()
    {
        return $this->home();
    }

    public function show(int $id, string $slug)
    {
        $db = $this->c->get('db');
        $md = $this->c->get('md');
        $t = Topic::find($db, $id);
        if (!$t) {
            http_response_code(404);
            exit('Topic not found');
        }
        $posts = Topic::posts($db, $id);
        $form = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);
        foreach ($posts as &$p) {
            $p['_score'] = Post::score($db, (int)$p['id']);
        }
        View::render('topics/show', [
            'title' => $t['title'],
            'topic' => $t,
            'posts' => $posts,
            'replyBody' => is_string($form['body'] ?? null) ? $form['body'] : '',
            'md' => $md,
            'auth' => $this->c->get('auth'),
            'csrf' => $this->c->get('csrf')
        ]);
    }

    public function new()
    {
        $this->c->get('auth')->requireLogin();
        $db = $this->c->get('db');
        $form = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);
        $selectedTags = is_array($form['tags'] ?? null) ? array_map('intval', $form['tags']) : [];
        View::render('topics/create', [
            'title' => 'New Topic',
            'form' => $form,
            'selectedTags' => $selectedTags,
            'tags' => Tag::all($db),
            'csrf' => $this->c->get('csrf')
        ]);
    }

    public function edit(int $id)
    {
        $auth = $this->c->get('auth');
        $auth->requireLogin();
        $db = $this->c->get('db');
        $topic = Topic::find($db, $id);
        if (!$topic) {
            http_response_code(404);
            exit('Topic not found');
        }
        if (!$this->canEdit($topic['user_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $form = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);
        $topicTags = array_key_exists('tags', $form)
            ? (is_array($form['tags']) ? array_map('intval', $form['tags']) : [])
            : Tag::forTopic($db, $id);
        View::render('topics/edit', [
            'title' => 'Edit Topic',
            'topic' => $topic,
            'form' => $form,
            'tags' => Tag::all($db),
            'topicTags' => $topicTags,
            'csrf' => $this->c->get('csrf')
        ]);
    }

    public function create()
    {
        $auth = $this->c->get('auth');
        $auth->requireLogin();
        $csrf = $this->c->get('csrf');
        if (!$csrf->validate(is_string($_POST['_token'] ?? null) ? $_POST['_token'] : '')) {
            exit('CSRF');
        }
        $db = $this->c->get('db');
        $title = $this->postString('title');
        $body = $this->postString('body');
        $tags = $this->normalizeTagIds($db, $_POST['tags'] ?? []);
        $errors = $this->validateContent($title, $body);
        if ($errors) {
            $_SESSION['flash'] = implode(' ', $errors);
            $_SESSION['old_input'] = [
                'title' => $title,
                'body' => $body,
                'tags' => $tags
            ];
            header('Location: /topics/new');
            return;
        }
        $slug = $this->c->get('slug')->make($title);
        $id = Topic::create($db, $auth->id(), $title, $slug, $body, $tags);
        header("Location: /topics/$id-$slug");
    }

    public function update(int $id)
    {
        $auth = $this->c->get('auth');
        $auth->requireLogin();
        $csrf = $this->c->get('csrf');
        if (!$csrf->validate(is_string($_POST['_token'] ?? null) ? $_POST['_token'] : '')) {
            exit('CSRF');
        }
        $db = $this->c->get('db');
        $topic = Topic::find($db, $id);
        if (!$topic) {
            http_response_code(404);
            exit('Topic not found');
        }
        if (!$this->canEdit($topic['user_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $title = $this->postString('title');
        $body = $this->postString('body');
        $tags = $this->normalizeTagIds($db, $_POST['tags'] ?? []);
        $errors = $this->validateContent($title, $body);
        if ($errors) {
            $_SESSION['flash'] = implode(' ', $errors);
            $_SESSION['old_input'] = [
                'title' => $title,
                'body' => $body,
                'tags' => $tags
            ];
            header("Location: /topics/$id/edit");
            return;
        }
        $slug = $this->c->get('slug')->make($title);
        Topic::update($db, $id, $title, $slug, $body, $tags);
        header("Location: /topics/$id-$slug");
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? trim($value) : '';
    }

    private function normalizeTagIds($db, mixed $submitted): array
    {
        if (!is_array($submitted)) {
            return [];
        }
        $allowed = array_fill_keys(array_map(static fn($tag) => (int)$tag['id'], Tag::all($db)), true);
        $ids = [];
        foreach ($submitted as $value) {
            if (!is_string($value) && !is_int($value)) {
                continue;
            }
            $id = filter_var($value, FILTER_VALIDATE_INT);
            if ($id !== false && $id > 0 && isset($allowed[$id])) {
                $ids[] = $id;
            }
        }
        return array_values(array_unique($ids));
    }

    private function canEdit(int $ownerId): bool
    {
        $auth = $this->c->get('auth');
        return $auth->id() === $ownerId || in_array($auth->role(), ['mod', 'admin'], true);
    }

    private function validateContent(string $title, string $body): array
    {
        $errors = [];
        if ($title === '') {
            $errors[] = 'A title is required.';
        } elseif (mb_strlen($title) < 5) {
            $errors[] = 'The title must be at least 5 characters.';
        } elseif (mb_strlen($title) > 180) {
            $errors[] = 'The title must be 180 characters or fewer.';
        }
        if ($body === '') {
            $errors[] = 'Question content is required.';
        } elseif (mb_strlen($body) < 10) {
            $errors[] = 'Question content must be at least 10 characters.';
        } elseif (mb_strlen($body) > 20000) {
            $errors[] = 'Question content must be 20,000 characters or fewer.';
        }
        return $errors;
    }
}
