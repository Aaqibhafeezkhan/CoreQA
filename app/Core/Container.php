<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class Container
{
    private array $cfg;
    private array $instances = [];

    public function __construct(array $cfg, ?PDO $db = null)
    {
        $this->cfg = $cfg;
        if ($db !== null) {
            $this->instances['db'] = $db;
        }
    }

    public function get(string $id)
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        return $this->instances[$id] = match ($id) {
            'config' => $this->cfg,
            'db' => (require __DIR__ . '/../../config/database.php')($this->cfg),
            'auth' => new \App\Services\Auth($this),
            'csrf' => new \App\Services\Csrf($this->cfg['csrf_key']),
            'md' => new \App\Services\Markdown(),
            'rate' => new \App\Services\RateLimiter($this->get('db')),
            'slug' => new \App\Services\Slug(),
            'mailer' => new \App\Services\Mailer($this->get('db')),
            'cache' => new \App\Services\Cache($this->cfg['cache_path'] ?? __DIR__ . '/../../storage/topics'),
            default => throw new \RuntimeException("Unknown service $id"),
        };
    }
}
