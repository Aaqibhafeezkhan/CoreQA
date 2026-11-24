<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/Core/Helpers.php';

return [
    'HTML escaping protects quotes ampersands and angle brackets' => static function (): void {
        $escaped = App\Core\e("\"'&<>");
        assertSame('&quot;&#039;&amp;&lt;&gt;', $escaped);
    },
    'HTML escaping substitutes invalid UTF-8' => static function (): void {
        $escaped = App\Core\e("bad\xFFvalue");
        assertContains('bad', $escaped);
        assertContains('value', $escaped);
    },
];
