<?php
declare(strict_types=1);

require_once __DIR__ . '/Assert.php';
require_once dirname(__DIR__) . '/config/autoload.php';

ob_start();

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

$testOutput = ob_get_clean();
if ($testOutput !== false) {
    echo $testOutput;
}

echo "Results: $passed passed, $failed failed.\n";
exit($failed === 0 ? 0 : 1);
