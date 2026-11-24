<?php
declare(strict_types=1);

return [
    'Shared application autoloader resolves classes from lowercase app directory' => static function (): void {
        assertTrue(class_exists(App\Core\DatabaseUrl::class));
        assertTrue(class_exists(App\Core\Container::class));
        assertTrue(class_exists(App\Services\Csrf::class));
        assertTrue(class_exists(App\Services\DatabaseSessionHandler::class));
        assertTrue(class_exists(App\Models\User::class));
    },
];
