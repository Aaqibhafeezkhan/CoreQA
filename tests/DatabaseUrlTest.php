<?php
declare(strict_types=1);

use App\Core\DatabaseUrl;

return [
    'PostgreSQL URL parser supports encoded credentials and Neon SSL mode' => static function (): void {
        $connection = DatabaseUrl::parse('postgresql://coreqa:secret%40value@ep-example-pooler.us-east-2.aws.neon.tech:5432/coreqa?sslmode=require&channel_binding=require');
        assertSame('coreqa', $connection['user']);
        assertSame('secret@value', $connection['pass']);
        assertContains('pgsql:host=ep-example-pooler.us-east-2.aws.neon.tech;port=5432;dbname=coreqa;sslmode=require', $connection['dsn']);
    },
    'PostgreSQL URL parser defaults to the secure PostgreSQL port and SSL' => static function (): void {
        $connection = DatabaseUrl::parse('postgres://user:password@localhost/forum');
        assertContains('port=5432', $connection['dsn']);
        assertContains('sslmode=require', $connection['dsn']);
    },
    'PostgreSQL URL parser rejects insecure SSL mode' => static function (): void {
        assertThrows(static fn() => DatabaseUrl::parse('postgresql://user:password@localhost/forum?sslmode=disable'), InvalidArgumentException::class);
    },
    'PostgreSQL URL parser rejects non-PostgreSQL URLs' => static function (): void {
        assertThrows(static fn() => DatabaseUrl::parse('mysql://user:password@localhost/forum'), InvalidArgumentException::class);
    },
];
