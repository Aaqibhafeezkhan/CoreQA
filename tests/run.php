<?php
declare(strict_types=1);

require_once __DIR__ . '/Assert.php';

spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'App\\') !== 0) {
        return;
    }
    $path = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$_SESSION = [];
$files = glob(__DIR__ . '/*Test.php') ?: [];
sort($files);
$passed = 0;
$failed = 0;

foreach ($files as $file) {
    $tests = require $file;
    foreach ($tests as $name => $test) {
        try {
            $test();
            $passed++;
            echo "PASS $name\n";
        } catch (Throwable $error) {
            $failed++;
            fwrite(STDERR, "FAIL $name: " . $error->getMessage() . "\n");
        }
    }
}

echo "Results: $passed passed, $failed failed.\n";
exit($failed === 0 ? 0 : 1);
