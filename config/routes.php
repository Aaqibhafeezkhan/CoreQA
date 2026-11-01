<?php

declare(strict_types=1);

use App\Controllers\{AuthController,TopicController,PostController,VoteController,AdminController,PanelController};

return function($r) {
    $r->get('/', [TopicController::class, 'home']);
    $r->get('/topics', [TopicController::class, 'index']);
    $r->get('/topics/{id:\\d+}-{slug:[a-z0-9\\-]+}', [TopicController::class, 'show']);
    $r->get('/topics/new', [TopicController::class, 'new']);
    $r->post('/topics/create', [TopicController::class, 'create']);
    $r->get('/topics/{id:\\d+}/edit', [TopicController::class, 'edit']);
    $r->post('/topics/{id:\\d+}/update', [TopicController::class, 'update']);
    $r->post('/topics/{id:\\d+}/reply', [PostController::class, 'reply']);
    $r->get('/posts/{id:\\d+}/edit', [PostController::class, 'edit']);
    $r->post('/posts/{id:\\d+}/update', [PostController::class, 'update']);
    $r->post('/posts/{id:\\d+}/vote', [VoteController::class, 'vote']);
    $r->get('/login', [AuthController::class, 'loginForm']);
    $r->post('/login', [AuthController::class, 'login']);
    $r->get('/register', [AuthController::class, 'registerForm']);
    $r->post('/register', [AuthController::class, 'register']);
    $r->post('/logout', [AuthController::class, 'logout']);
    $r->get('/me', [PanelController::class, 'me']);
    $r->post('/me/account', [PanelController::class, 'updateAccount']);
    $r->post('/me/password', [PanelController::class, 'updatePassword']);
    $r->get('/panel/admin', [AdminController::class, 'dashboard']);
    $r->get('/panel/admin/users/{id:\\d+}/edit', [AdminController::class, 'editUser']);
    $r->post('/panel/admin/users/{id:\\d+}/update', [AdminController::class, 'updateUser']);
    $r->post('/panel/admin/users/{id:\\d+}/role', [AdminController::class, 'setRole']);
    $r->post('/panel/admin/users/{id:\\d+}/delete', [AdminController::class, 'deleteUser']);
    $r->post('/panel/admin/tags/create', [AdminController::class, 'createTag']);
    $r->post('/panel/admin/tags/{id:\\d+}/delete', [AdminController::class, 'deleteTag']);
    $r->post('/panel/admin/topics/{id:\\d+}/pin', [AdminController::class, 'pin']);
    $r->post('/panel/admin/topics/{id:\\d+}/toggle', [AdminController::class, 'toggleTopic']);
    $r->post('/panel/admin/topics/{id:\\d+}/delete', [AdminController::class, 'deleteTopic']);
    $r->get('/panel/mod', [PanelController::class, 'mod']);
    $r->post('/panel/mod/topics/{id:\\d+}/toggle', [PanelController::class, 'toggleTopic']);
    $r->post('/panel/mod/votes/{postId:\\d+}/reset', [PanelController::class, 'resetVotes']);
    $r->post('/panel/mod/posts/{postId:\\d+}/delete', [PanelController::class, 'deletePost']);
};
