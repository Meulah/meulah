<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$directories = [
    'app',
    'start',
    'settings',
    'routes',
    'views',
    'database',
    'public',
    'tests',
];
$files = [];

foreach ($directories as $directory) {
    $path = $root . DIRECTORY_SEPARATOR . $directory;

    if (!is_dir($path)) {
        throw new RuntimeException("Required application directory is missing: {$directory}/");
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

$launcher = $root . DIRECTORY_SEPARATOR . 'meulah';

if (!is_file($launcher)) {
    throw new RuntimeException('The root Meulah launcher is missing.');
}

$files[] = $launcher;
sort($files, SORT_STRING);

foreach ($files as $file) {
    $process = proc_open(
        [PHP_BINARY, '-l', $file],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $root,
    );

    if (!is_resource($process)) {
        throw new RuntimeException("Unable to lint: {$file}");
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0) {
        $output = trim(($stdout === false ? '' : $stdout) . PHP_EOL . ($stderr === false ? '' : $stderr));

        throw new RuntimeException("PHP lint failed for {$file}: {$output}");
    }
}

echo 'Linted ' . count($files) . " application PHP files.\n";
