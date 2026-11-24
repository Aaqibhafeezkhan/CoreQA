<?php
declare(strict_types=1);

namespace App\Services;

class Markdown
{
    public function toHtml(string $markdown): string
    {
        $lines = preg_split('/\R/u', $markdown);
        if (!is_array($lines)) {
            return htmlspecialchars($markdown, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $output = [];
        $fenced = false;
        $code = [];

        foreach ($lines as $line) {
            if (preg_match('/^\s*\x60{3}(?:[a-zA-Z0-9_-]+)?\s*$/', $line) === 1) {
                if ($fenced) {
                    $output[] = $this->codeBlock($code);
                    $code = [];
                    $fenced = false;
                } else {
                    $fenced = true;
                }
                continue;
            }

            if ($fenced) {
                $code[] = $line;
                continue;
            }

            $output[] = nl2br($this->inline($line), false);
        }

        if ($fenced) {
            $output[] = $this->codeBlock($code);
        }

        return implode("\n", $output);
    }

    private function codeBlock(array $lines): string
    {
        $code = htmlspecialchars(implode("\n", $lines), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<pre><code>' . $code . '</code></pre>';
    }

    private function inline(string $line): string
    {
        $tokens = [];
        $reserve = static function (string $html) use (&$tokens): string {
            $token = 'COREQATOKEN' . strtoupper(bin2hex(random_bytes(8))) . 'END';
            $tokens[$token] = $html;
            return $token;
        };

        $text = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $text = preg_replace_callback('/\x60([^\x60]+)\x60/', static function (array $match) use ($reserve): string {
            return $reserve('<code>' . $match[1] . '</code>');
        }, $text) ?? $text;

        $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)(?:\s+"([^"]*)")?\)/', function (array $match) use ($reserve): string {
            $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!$this->safeUrl($url, true)) {
                return $match[0];
            }

            $src = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return $reserve('<img src="' . $src . '" alt="' . $match[1] . '" loading="lazy" decoding="async" referrerpolicy="no-referrer">');
        }, $text) ?? $text;

        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)(?:\s+"([^"]*)")?\)/', function (array $match) use ($reserve): string {
            $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (!$this->safeUrl($url, false)) {
                return $match[0];
            }

            $href = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $external = preg_match('/^https?:/i', $url) === 1;
            $attributes = $external ? ' target="_blank" rel="nofollow noopener noreferrer"' : '';
            return $reserve('<a href="' . $href . '"' . $attributes . '>' . $match[1] . '</a>');
        }, $text) ?? $text;

        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $text) ?? $text;

        return strtr($text, $tokens);
    }

    private function safeUrl(string $url, bool $image): bool
    {
        $url = trim($url);
        if ($url === '' || str_contains($url, '\\') || preg_match('/[\x00-\x20\x7f]/', $url) === 1) {
            return false;
        }

        $normalized = preg_replace('/[\x00-\x20\x7f]+/', '', $url);
        if (!is_string($normalized) || $normalized === '' || str_starts_with($normalized, '//')) {
            return false;
        }

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $normalized, $schemeMatch) === 1) {
            $scheme = strtolower($schemeMatch[1]);
            return $image
                ? in_array($scheme, ['http', 'https'], true)
                : in_array($scheme, ['http', 'https', 'mailto'], true);
        }

        return !$image;
    }
}
