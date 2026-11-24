<?php
declare(strict_types=1);

function assertTrue(bool $condition, string $message = 'Expected condition to be true.'): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException($message !== '' ? $message : 'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.');
    }
}

function assertContains(string $needle, string $haystack, string $message = ''): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException($message !== '' ? $message : 'Expected output to contain ' . $needle . '.');
    }
}

function assertNotContains(string $needle, string $haystack, string $message = ''): void
{
    if (str_contains($haystack, $needle)) {
        throw new RuntimeException($message !== '' ? $message : 'Expected output not to contain ' . $needle . '.');
    }
}

function assertThrows(callable $callback, string $class = Throwable::class): void
{
    try {
        $callback();
    } catch (Throwable $error) {
        if (!$error instanceof $class) {
            throw new RuntimeException('Expected ' . $class . ', got ' . get_class($error) . '.');
        }
        return;
    }

    throw new RuntimeException('Expected an exception of type ' . $class . '.');
}
