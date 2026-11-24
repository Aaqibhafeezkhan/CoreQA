<?php
declare(strict_types=1);

use App\Core\Container;
use App\Core\Router;

return [
    'Router dispatches static routes' => static function (): void {
        $router = new Router(new Container([]));
        $called = false;
        $router->get('/health', static function () use (&$called): void {
            $called = true;
        });
        $router->dispatch('GET', '/health');
        assertTrue($called);
    },
    'Router dispatches parameter routes' => static function (): void {
        $router = new Router(new Container([]));
        $captured = null;
        $router->get('/topics/{id:\d+}', static function (string $id) use (&$captured): void {
            $captured = $id;
        });
        $router->dispatch('GET', '/topics/42');
        assertSame('42', $captured);
    },
    'Router returns not found for unknown and wrong methods' => static function (): void {
        $router = new Router(new Container([]));
        $router->post('/write', static function (): void {
        });
        ob_start();
        $router->dispatch('GET', '/missing');
        $unknown = ob_get_clean();
        assertSame('Not Found', $unknown);
        ob_start();
        $router->dispatch('GET', '/write');
        $wrongMethod = ob_get_clean();
        assertSame('Not Found', $wrongMethod);
    },
];
