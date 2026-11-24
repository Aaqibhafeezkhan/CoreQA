<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
$failures = [];
$checked = 0;

foreach ($iterator as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $relative = substr($file->getPathname(), strlen($root) + 1);
    if (str_starts_with($relative, 'vendor/') || str_starts_with($relative, 'storage/')) {
        continue;
    }

    $checked++;
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1';
    exec($command, $output, $status);
    if ($status !== 0) {
        $failures[$relative] = implode("\n", $output);
    }
    $output = [];
}

foreach ($failures as $path => $message) {
    fwrite(STDERR, "FAIL $path\n$message\n");
}

if ($failures) {
    fwrite(STDERR, count($failures) . " PHP file(s) failed syntax validation.\n");
    exit(1);
}

echo "PHP syntax validation passed for $checked file(s).\n";
