<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$launcher = $root . DIRECTORY_SEPARATOR . 'meulah';

function cliEnsure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * @param list<string> $command
 * @param array<string, string> $environment
 * @return array{int, string, string}
 */
function runCliProcess(
    array $command,
    string $workingDirectory,
    array $environment = [],
): array {
    $processEnvironment = getenv();
    $processEnvironment = is_array($processEnvironment) ? $processEnvironment : [];

    unset($processEnvironment['NO_COLOR'], $processEnvironment['CI']);

    foreach ($environment as $key => $value) {
        $processEnvironment[$key] = $value;
    }

    $process = proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $workingDirectory,
        $processEnvironment,
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start a CLI test process.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        proc_close($process),
        $stdout === false ? '' : $stdout,
        $stderr === false ? '' : $stderr,
    ];
}

/**
 * @param list<string> $arguments
 * @param array<string, string> $environment
 * @return array{int, string, string}
 */
function runLauncher(
    string $launcher,
    array $arguments,
    string $workingDirectory,
    array $environment = [],
): array {
    return runCliProcess(
        [PHP_BINARY, $launcher, ...$arguments],
        $workingDirectory,
        $environment,
    );
}

[$exitCode, $stdout, $stderr] = runLauncher($launcher, ['--help'], $root);
cliEnsure($exitCode === 0, 'Global help failed: ' . $stderr);
cliEnsure(str_contains($stdout, 'Meulah CLI'), 'Global help omitted the CLI name.');
cliEnsure(str_contains($stdout, 'Global options:'), 'Global help omitted global options.');

[$exitCode, $stdout, $stderr] = runLauncher($launcher, ['--version'], dirname($root));
cliEnsure($exitCode === 0, 'Global version failed outside the project root: ' . $stderr);
cliEnsure(
    preg_match('/Meulah CLI.*v?0\.2\.[0-9]+/s', $stdout) === 1,
    'Global version did not report Framework 0.2.',
);

[$exitCode, $stdout, $stderr] = runLauncher($launcher, ['--help', '--ansi'], $root);
cliEnsure($exitCode === 0, 'Forced ANSI help failed: ' . $stderr);
cliEnsure(str_contains($stdout, "\033["), '--ansi did not force ANSI output.');

[$exitCode, $stdout, $stderr] = runLauncher($launcher, ['--help', '--no-ansi'], $root);
cliEnsure($exitCode === 0, 'Disabled ANSI help failed: ' . $stderr);
cliEnsure(!str_contains($stdout, "\033["), '--no-ansi emitted ANSI output.');

[$exitCode, $stdout, $stderr] = runLauncher(
    $launcher,
    ['--help'],
    $root,
    ['NO_COLOR' => '1'],
);
cliEnsure($exitCode === 0, 'NO_COLOR help failed: ' . $stderr);
cliEnsure(!str_contains($stdout, "\033["), 'NO_COLOR did not disable ANSI output.');

[$exitCode, $stdout, $stderr] = runLauncher(
    $launcher,
    ['--help', '--ansi'],
    $root,
    ['NO_COLOR' => '1'],
);
cliEnsure($exitCode === 0, 'Explicit ANSI override failed: ' . $stderr);
cliEnsure(str_contains($stdout, "\033["), '--ansi did not override NO_COLOR explicitly.');

$identifier = bin2hex(random_bytes(6));
$databaseRelative = "data/cli-test-{$identifier}.sqlite";
$databasePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $databaseRelative);
$spaceWorkingDirectory = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'meulah cli working directory '
    . $identifier;

if (!mkdir($spaceWorkingDirectory, 0777, true) && !is_dir($spaceWorkingDirectory)) {
    throw new RuntimeException('Unable to create the CLI working directory.');
}

try {
    $databaseEnvironment = [
        'APP_ENV' => 'development',
        'APP_DEBUG' => 'false',
        'DB_DRIVER' => 'sqlite',
        'DB_PATH' => $databaseRelative,
    ];

    foreach ([
        [$root, ['migrate:status']],
        [dirname($root), ['migrate']],
        [$root . '/app/Controllers', ['migrate:status']],
        [$spaceWorkingDirectory, ['migrate:status']],
    ] as [$workingDirectory, $arguments]) {
        [$exitCode, $stdout, $stderr] = runLauncher(
            $launcher,
            $arguments,
            $workingDirectory,
            $databaseEnvironment,
        );
        cliEnsure(
            $exitCode === 0,
            'Application command failed outside the project root: ' . $stdout . $stderr,
        );
    }

    cliEnsure(is_file($databasePath), 'The launcher did not use the explicit application root.');
} finally {
    if (is_file($databasePath)) {
        unlink($databasePath);
    }

    if (is_dir($spaceWorkingDirectory)) {
        rmdir($spaceWorkingDirectory);
    }
}

$missingVendorRoot = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'meulah missing vendor '
    . bin2hex(random_bytes(6));

if (!mkdir($missingVendorRoot, 0777, true) && !is_dir($missingVendorRoot)) {
    throw new RuntimeException('Unable to create the missing-vendor fixture.');
}

try {
    $fixtureLauncher = $missingVendorRoot . DIRECTORY_SEPARATOR . 'meulah';
    copy($launcher, $fixtureLauncher);

    [$exitCode, $stdout, $stderr] = runLauncher(
        $fixtureLauncher,
        ['--help'],
        $missingVendorRoot,
    );
    $combinedOutput = $stdout . $stderr;

    cliEnsure($exitCode === 1, 'The missing-vendor launcher did not fail.');
    cliEnsure(str_contains($stderr, 'Composer dependencies are missing.'), 'Missing vendor error is unclear.');
    cliEnsure(str_contains($stderr, 'composer install'), 'Missing vendor error omitted recovery guidance.');
    cliEnsure(!str_contains($combinedOutput, 'Stack trace'), 'Missing vendor output exposed a stack trace.');
    cliEnsure(!str_contains($combinedOutput, $missingVendorRoot), 'Missing vendor output exposed a local path.');
} finally {
    if (isset($fixtureLauncher) && is_file($fixtureLauncher)) {
        unlink($fixtureLauncher);
    }

    if (is_dir($missingVendorRoot)) {
        rmdir($missingVendorRoot);
    }
}

if (PHP_OS_FAMILY === 'Windows') {
    cliEnsure(str_contains($launcher, '\\'), 'The Windows launcher test did not use a Windows path.');

    $commandPrompt = getenv('ComSpec');
    $commandPrompt = is_string($commandPrompt) && $commandPrompt !== '' ? $commandPrompt : 'cmd.exe';
    $batchDirectory = $root . '/runtime/cache';
    $batchName = 'meulah-cli-' . $identifier . '.cmd';
    $batchPath = $batchDirectory . DIRECTORY_SEPARATOR . $batchName;

    try {
        file_put_contents(
            $batchPath,
            "@echo off\r\n\"" . PHP_BINARY . "\" \"" . $launcher . "\" --version\r\n",
        );
        [$exitCode, $stdout, $stderr] = runCliProcess(
            [$commandPrompt, '/d', '/c', $batchName],
            $batchDirectory,
        );
        cliEnsure($exitCode === 0 && str_contains($stdout, 'Meulah CLI'), 'Command Prompt execution failed: ' . $stderr);
    } finally {
        if (is_file($batchPath)) {
            unlink($batchPath);
        }
    }

    $systemRoot = getenv('SystemRoot');
    $powershell = is_string($systemRoot)
        ? $systemRoot . '\\System32\\WindowsPowerShell\\v1.0\\powershell.exe'
        : 'powershell.exe';
    $powershellCommand = "& '"
        . str_replace("'", "''", PHP_BINARY)
        . "' '"
        . str_replace("'", "''", $launcher)
        . "' --version";
    [$exitCode, $stdout, $stderr] = runCliProcess(
        [$powershell, '-NoLogo', '-NoProfile', '-NonInteractive', '-Command', $powershellCommand],
        dirname($root),
    );
    cliEnsure($exitCode === 0 && str_contains($stdout, 'Meulah CLI'), 'PowerShell execution failed: ' . $stderr);
} else {
    cliEnsure(is_executable($launcher), 'The Unix launcher is not executable.');
}

echo "CLI launcher tests passed.\n";
