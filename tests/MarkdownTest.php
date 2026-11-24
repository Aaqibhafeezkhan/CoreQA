<?php
declare(strict_types=1);

use App\Services\Markdown;

return [
    'Markdown escapes raw HTML and script elements' => static function (): void {
        $html = (new Markdown())->toHtml('<script>alert(1)</script><img src=x onerror=alert(1)>');
        assertNotContains('<script>', $html);
        assertNotContains('<img src=x', $html);
        assertContains('&lt;script&gt;', $html);
    },
    'Markdown blocks dangerous link and image schemes' => static function (): void {
        $markdown = new Markdown();
        $links = $markdown->toHtml('[click](javascript:alert(1)) [data](data:text/html,hello)');
        $images = $markdown->toHtml('![x](javascript:alert(1)) ![y](data:image/svg+xml,abc)');
        assertNotContains('href="javascript:', $links);
        assertNotContains('href="data:', $links);
        assertNotContains('<img', $images);
    },
    'Markdown preserves safe links and emphasis' => static function (): void {
        $html = (new Markdown())->toHtml('**bold** and *italic* [docs](https://example.com)');
        assertContains('<strong>bold</strong>', $html);
        assertContains('<em>italic</em>', $html);
        assertContains('href="https://example.com"', $html);
        assertContains('rel="nofollow noopener noreferrer"', $html);
    },
    'Markdown renders and closes fenced code blocks' => static function (): void {
        $html = (new Markdown())->toHtml("\x60\x60\x60php\n<?php echo 1;\n");
        assertContains('<pre><code>', $html);
        assertContains('&lt;?php echo 1;', $html);
        assertNotContains('<?php echo 1;', $html);
        assertContains('</code></pre>', $html);
    },
    'Markdown escapes malformed UTF-8' => static function (): void {
        $html = (new Markdown())->toHtml("bad\xFFvalue");
        assertContains('bad', $html);
        assertContains('value', $html);
    },
];
