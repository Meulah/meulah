<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Meulah\Config\Repository;
use Meulah\Support\Environment;

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

function distributionEnsure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @return list<string>|null */
function trackedFiles(string $root): ?array
{
    if (!is_dir($root . '/.git')) {
        return null;
    }

    $process = proc_open(
        ['git', 'ls-files', '-z'],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $root,
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to inspect the Git distribution.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    if ($exitCode !== 0 || $stdout === false) {
        throw new RuntimeException('Unable to inspect tracked files: ' . trim($stderr === false ? '' : $stderr));
    }

    return array_values(array_filter(explode("\0", $stdout), static fn (string $file): bool => $file !== ''));
}

/** @return list<string> */
function applicationSourceFiles(string $root): array
{
    $files = ['.env.example', 'README.md', 'SECURITY.md', 'composer.json', 'meulah'];

    foreach (['app', 'start', 'settings', 'routes', 'views', 'database', 'public'] as $directory) {
        $path = $root . '/' . $directory;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->isLink()) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($root) + 1);
            $files[] = str_replace('\\', '/', $relative);
        }
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_STRING);

    return $files;
}

/** @return array<string, mixed> */
function readJsonFile(string $path): array
{
    $contents = file_get_contents($path);

    if (!is_string($contents)) {
        throw new RuntimeException("Unable to read JSON file: {$path}");
    }

    $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($decoded)) {
        throw new RuntimeException("JSON root must be an object: {$path}");
    }

    return $decoded;
}

function normalizedPath(string $path): string
{
    $normalized = rtrim(str_replace('\\', '/', $path), '/');

    return DIRECTORY_SEPARATOR === '\\' ? strtolower($normalized) : $normalized;
}

function pathContains(string $parent, string $child): bool
{
    $parent = normalizedPath($parent);
    $child = normalizedPath($child);

    return $child === $parent || str_starts_with($child, $parent . '/');
}

$composer = readJsonFile($root . '/composer.json');
$lock = readJsonFile($root . '/composer.lock');

distributionEnsure(
    ($composer['require']['meulah/framework'] ?? null) === '^0.2',
    'The starter must require meulah/framework:^0.2.',
);
distributionEnsure(!array_key_exists('repositories', $composer), 'Local Composer repositories are not allowed.');
distributionEnsure(!array_key_exists('minimum-stability', $composer), 'The starter must not need development stability.');
distributionEnsure(
    !isset($composer['require-dev']) || $composer['require-dev'] === [],
    'Application runtime smoke tests must not depend on require-dev packages.',
);

$frameworkPackage = null;

foreach ($lock['packages'] ?? [] as $package) {
    if (is_array($package) && ($package['name'] ?? null) === 'meulah/framework') {
        $frameworkPackage = $package;
        break;
    }
}

distributionEnsure(is_array($frameworkPackage), 'The framework is missing from composer.lock.');
$lockedVersion = $frameworkPackage['version'] ?? null;
distributionEnsure(
    is_string($lockedVersion) && preg_match('/^v?0\.2\.[0-9]+$/', $lockedVersion) === 1,
    'composer.lock must contain a tagged Framework 0.2.x release.',
);
distributionEnsure(
    ($frameworkPackage['dist']['type'] ?? null) === 'zip'
        && is_string($frameworkPackage['dist']['url'] ?? null)
        && str_starts_with($frameworkPackage['dist']['url'], 'https://'),
    'Framework 0.2 must resolve from a public Composer distribution archive.',
);
distributionEnsure(
    ($frameworkPackage['source']['url'] ?? null) === 'https://github.com/Meulah/framework.git',
    'Framework source metadata points to an unexpected repository.',
);

$installedVersion = InstalledVersions::getPrettyVersion('meulah/framework');
distributionEnsure(
    is_string($installedVersion) && preg_match('/^v?0\.2\.[0-9]+$/', $installedVersion) === 1,
    'The installed framework is not a tagged 0.2.x release.',
);

$composerSource = file_get_contents($root . '/composer.json');
$lockSource = file_get_contents($root . '/composer.lock');
distributionEnsure(is_string($composerSource) && is_string($lockSource), 'Composer metadata could not be inspected.');
distributionEnsure(
    !str_contains($composerSource . $lockSource, 'dev-main'),
    'Development framework branches must not appear in distribution metadata.',
);

Environment::load($root . '/.env.example');
$settings = Repository::load($root . '/settings');
$projectRoot = normalizedPath($root);
$public = normalizedPath($root . '/public');
$runtime = normalizedPath($root . '/runtime');
$data = normalizedPath($root . '/data');
$uploads = normalizedPath($settings->string('files.uploads'));
$database = normalizedPath($settings->string('database.path'));

distributionEnsure(is_dir($root . '/settings'), 'Application settings must live under settings/.');
distributionEnsure(is_dir($root . '/views'), 'Application views must live under views/.');
distributionEnsure(pathContains($projectRoot . '/settings', $root . '/settings'), 'Settings escaped settings/.');
distributionEnsure(pathContains($projectRoot . '/views', $root . '/views'), 'Views escaped views/.');
distributionEnsure(pathContains($runtime, $settings->string('files.cache')), 'Cache escaped runtime/.');
distributionEnsure(pathContains($runtime, $settings->string('files.logs')), 'Logs escaped runtime/.');
distributionEnsure(pathContains($runtime, $settings->string('files.sessions')), 'Sessions escaped runtime/.');
distributionEnsure(pathContains($runtime, $settings->string('files.views')), 'View cache escaped runtime/.');
distributionEnsure(pathContains($data, $uploads), 'Uploads escaped data/.');
distributionEnsure(pathContains($data, $database), 'SQLite escaped data/.');
distributionEnsure(!pathContains($public, $data), 'Persistent data is exposed beneath public/.');
distributionEnsure(!pathContains($public, $runtime), 'Runtime data is exposed beneath public/.');
distributionEnsure(!pathContains($runtime, $uploads), 'Persistent uploads would be removed by runtime cleanup.');
distributionEnsure(!pathContains($runtime, $database), 'The database would be removed by runtime cleanup.');

foreach ([
    'runtime/cache',
    'runtime/logs',
    'runtime/sessions',
    'runtime/views',
    'data/uploads',
] as $directory) {
    $path = $root . '/' . $directory;
    distributionEnsure(is_dir($path), "Required directory is missing: {$directory}/");
    distributionEnsure(!is_link($path), "Storage boundary must not be a symlink: {$directory}/");
}

$publicIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/public', FilesystemIterator::SKIP_DOTS),
);

foreach ($publicIterator as $entry) {
    distributionEnsure(!$entry->isLink(), 'public/ must not expose application data through a symlink.');
}

$tracked = trackedFiles($root);
if ($tracked !== null) {
    $forbiddenTracked = array_values(array_filter(
        $tracked,
        static function (string $file): bool {
            return $file === '.env'
                || (str_starts_with($file, '.env.') && $file !== '.env.example')
                || str_starts_with($file, 'vendor/')
                || $file === 'data/database.sqlite'
                || (str_starts_with($file, 'data/uploads/') && $file !== 'data/uploads/.gitignore')
                || (
                    str_starts_with($file, 'runtime/')
                    && preg_match('#^runtime/(cache|logs|sessions|views)/\.gitignore$#', $file) !== 1
                );
        },
    ));
    distributionEnsure(
        $forbiddenTracked === [],
        'Generated or private distribution files are tracked: ' . implode(', ', $forbiddenTracked),
    );
}

foreach (applicationSourceFiles($root) as $file) {
    $contents = file_get_contents($root . '/' . $file);

    if (!is_string($contents)) {
        continue;
    }

    distributionEnsure(
        preg_match('#[A-Za-z]:[\\\\/]Users[\\\\/]#i', $contents) !== 1
            && preg_match('#/Users/[^/]+/#', $contents) !== 1
            && preg_match('#/home/[^/]+/#', $contents) !== 1,
        "Application file contains an absolute developer path: {$file}",
    );
}

echo "Distribution and file-boundary tests passed with Framework {$installedVersion}.\n";
