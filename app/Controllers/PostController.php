<?php

namespace App\Controllers;

use App\Models\Post;
use App\Models\Topic;

class PostController
{
    public function __construct(private \App\Core\Container $c) {}

    public function reply(int $id)
    {
        $auth = $this->c->get('auth');
        $auth->requireLogin();
        $csrf = $this->c->get('csrf');
        if (!$csrf->validate(is_string($_POST['_token'] ?? null) ? $_POST['_token'] : '')) {
            exit('CSRF');
        }
        $topic = Topic::find($this->c->get('db'), $id);
        if (!$topic) {
            http_response_code(404);
            exit('Topic not found');
        }
        $body = is_string($_POST['body'] ?? null) ? trim($_POST['body']) : '';
        if ($topic['status'] !== 'open') {
            $_SESSION['flash'] = 'This conversation is closed.';
            header("Location: /topics/$id-" . ($topic['slug'] ?? ''));
            return;
        }
        $errors = $this->validateBody($body);
        if ($errors) {
            $_SESSION['flash'] = implode(' ', $errors);
            $_SESSION['old_input'] = ['body' => $body];
            header("Location: /topics/$id-" . ($topic['slug'] ?? ''));
            return;
        }
        Post::create($this->c->get('db'), $id, $auth->id(), $body);
        $this->c->get('cache')->forget('topic_'.$id);
        header("Location: /topics/$id-" . ($topic['slug'] ?? ''));
    }

    public function edit(int $id)
    {
        $auth = $this->c->get('auth');
        $auth->requireLogin();
        $db = $this->c->get('db');
        $post = Post::find($db, $id);
        if (!$post) {
            http_response_code(404);
            exit('Answer not found');
        }
        if (!$this->canEdit($post['user_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $topic = Topic::find($db, (int)$post['topic_id']);
        if (!$topic) {
            http_response_code(404);
            exit('Topic not found');
        }
        $form = $_SESSION['old_input'] ?? [];
        unset($_SESSION['old_input']);
        \App\Core\View::render('posts/edit', [
            'title' => 'Edit Answer',
            'post' => $post,
            'topic' => $topic,
            'form' => $form,
            'csrf' => $this->c->get('csrf')
        ]);
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
        $post = Post::find($db, $id);
        if (!$post) {
            http_response_code(404);
            exit('Answer not found');
        }
        if (!$this->canEdit($post['user_id'])) {
            http_response_code(403);
            exit('Forbidden');
        }
        $topic = Topic::find($db, (int)$post['topic_id']);
        if (!$topic) {
            http_response_code(404);
            exit('Topic not found');
        }
        $body = is_string($_POST['body'] ?? null) ? trim($_POST['body']) : '';
        $errors = $this->validateBody($body);
        if ($errors) {
            $_SESSION['flash'] = implode(' ', $errors);
            $_SESSION['old_input'] = ['body' => $body];
            header("Location: /posts/$id/edit");
            return;
        }
        Post::update($db, $id, $body);
        $this->c->get('cache')->forget('topic_'.$post['topic_id']);
        header("Location: /topics/{$post['topic_id']}-" . ($topic['slug'] ?? ''));
    }

    private function canEdit(int $ownerId): bool
    {
        $auth = $this->c->get('auth');
        return $auth->id() === $ownerId || in_array($auth->role(), ['mod', 'admin'], true);
    }

    private function validateBody(string $body): array
    {
        $errors = [];
        if ($body === '') {
            $errors[] = 'Answer content is required.';
        } elseif (mb_strlen($body) < 10) {
            $errors[] = 'Answer content must be at least 10 characters.';
        } elseif (mb_strlen($body) > 20000) {
            $errors[] = 'Answer content must be 20,000 characters or fewer.';
        }
        return $errors;
    }
}
