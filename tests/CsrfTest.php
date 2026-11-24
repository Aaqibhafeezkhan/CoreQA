<?php
declare(strict_types=1);

use App\Services\Csrf;

return [
    'CSRF accepts the current token' => static function (): void {
        $_SESSION = [];
        $csrf = new Csrf(str_repeat('k', 64));
        assertTrue($csrf->validate($csrf->token()));
    },
    'CSRF rejects missing empty and wrong tokens' => static function (): void {
        $_SESSION = [];
        $csrf = new Csrf(str_repeat('k', 64));
        assertSame(false, $csrf->validate(null));
        assertSame(false, $csrf->validate(''));
        assertSame(false, $csrf->validate('wrong-token'));
    },
    'CSRF rotation invalidates the previous token' => static function (): void {
        $_SESSION = [];
        $csrf = new Csrf(str_repeat('k', 64));
        $old = $csrf->token();
        $new = $csrf->rotate();
        assertNotContains($old, [$new]);
        assertSame(false, $csrf->validate($old));
        assertTrue($csrf->validate($new));
    },
];
