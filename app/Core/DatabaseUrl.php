<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

final class DatabaseUrl
{
    public static function parse(string $url): array
    {
        $parts = parse_url($url);
        if (!is_array($parts)
            || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['postgres', 'postgresql'], true)
            || empty($parts['host'])
            || empty($parts['path'])
            || trim((string)$parts['path'], '/') === '') {
            throw new InvalidArgumentException('DATABASE_URL must be a valid PostgreSQL connection URL.');
        }

        $query = [];
        parse_str((string)($parts['query'] ?? ''), $query);
        $sslMode = strtolower((string)($query['sslmode'] ?? 'require'));
        if (!in_array($sslMode, ['require', 'verify-ca', 'verify-full'], true)) {
            throw new InvalidArgumentException('DATABASE_URL must use an SSL-enabled PostgreSQL connection.');
        }

        $host = self::escapeDsnValue((string)$parts['host']);
        $database = self::escapeDsnValue(rawurldecode(trim((string)$parts['path'], '/')));
        $port = isset($parts['port']) ? (int)$parts['port'] : 5432;
        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('DATABASE_URL contains an invalid database port.');
        }

        return [
            'dsn' => 'pgsql:host=' . $host . ';port=' . $port . ';dbname=' . $database . ';sslmode=' . $sslMode,
            'user' => rawurldecode((string)($parts['user'] ?? '')),
            'pass' => rawurldecode((string)($parts['pass'] ?? '')),
        ];
    }

    private static function escapeDsnValue(string $value): string
    {
        return str_replace(['\\', ';'], ['\\\\', '\\;'], $value);
    }
}
