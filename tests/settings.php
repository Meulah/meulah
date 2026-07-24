<?php

declare(strict_types=1);

use Meulah\Config\Repository;
use Meulah\Database\Connection;
use Meulah\Support\Environment;

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

function settingsEnsure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/** @param array<string, mixed> $values */
function withEnvironment(array $values, callable $callback): mixed
{
    $keys = [
        'APP_NAME',
        'APP_ENV',
        'APP_DEBUG',
        'APP_TIMEZONE',
        'HTTP_MAX_BODY_SIZE',
        'DB_DRIVER',
        'DB_PATH',
        'DB_HOST',
        'DB_PORT',
        'DB_NAME',
        'DB_USER',
        'DB_PASS',
        'DB_MIGRATIONS',
        'DB_MIGRATION_TABLE',
        'UPLOAD_PATH',
        'CACHE_PATH',
        'LOG_PATH',
        'SESSION_PATH',
        'VIEW_CACHE_PATH',
    ];
    $original = [];

    foreach ($keys as $key) {
        $original[$key] = [
            'env_exists' => array_key_exists($key, $_ENV),
            'env' => $_ENV[$key] ?? null,
            'server_exists' => array_key_exists($key, $_SERVER),
            'server' => $_SERVER[$key] ?? null,
            'process' => getenv($key),
        ];
        unset($_ENV[$key], $_SERVER[$key]);
        putenv($key);
    }

    foreach ($values as $key => $value) {
        $_ENV[$key] = $value;
    }

    try {
        return $callback();
    } finally {
        foreach ($keys as $key) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);
            $state = $original[$key];

            if ($state['env_exists']) {
                $_ENV[$key] = $state['env'];
            }

            if ($state['server_exists']) {
                $_SERVER[$key] = $state['server'];
            }

            if (is_string($state['process'])) {
                putenv($key . '=' . $state['process']);
            }
        }
    }
}

function expectInvalid(callable $callback, string $message): void
{
    try {
        $callback();
    } catch (InvalidArgumentException) {
        return;
    }

    throw new RuntimeException($message);
}

/**
 * @param list<string> $command
 * @param array<string, string> $environment
 * @return array{int, string, string}
 */
function runSettingsProcess(
    array $command,
    string $workingDirectory,
    array $environment = [],
): array {
    $processEnvironment = getenv();
    $processEnvironment = is_array($processEnvironment) ? $processEnvironment : [];

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
        throw new RuntimeException('Unable to start a settings test process.');
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

withEnvironment([], static function () use ($root): void {
    Environment::load($root . '/.env.example');
    $settings = Repository::load($root . '/settings');

    settingsEnsure($settings->string('app.name') === 'Meulah', 'APP_NAME did not load.');
    settingsEnsure($settings->string('app.environment') === 'development', 'APP_ENV did not load.');
    settingsEnsure($settings->bool('app.debug'), 'APP_DEBUG did not load as a boolean.');
    settingsEnsure($settings->string('app.timezone') === 'UTC', 'APP_TIMEZONE did not load.');
    settingsEnsure($settings->int('http.max_body_size') === 10_485_760, 'HTTP limit is invalid.');
    settingsEnsure($settings->string('database.driver') === 'sqlite', 'SQLite is not the default.');
    settingsEnsure(
        $settings->string('database.path') === $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'database.sqlite',
        'The default SQLite path is incorrect.',
    );
    settingsEnsure(
        $settings->string('files.uploads') === $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'uploads',
        'The upload path is incorrect.',
    );

    foreach (['cache', 'logs', 'sessions', 'views'] as $key) {
        settingsEnsure(
            str_starts_with(
                $settings->string("files.{$key}"),
                $root . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR,
            ),
            "files.{$key} must remain under runtime/.",
        );
    }

    $public = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;
    settingsEnsure(!str_starts_with($settings->string('database.path'), $public), 'Database is public.');
    settingsEnsure(!str_starts_with($settings->string('files.uploads'), $public), 'Uploads are public.');
});

$quotedEnvironment = $root . '/runtime/cache/settings-quoted-' . bin2hex(random_bytes(6)) . '.env';

try {
    file_put_contents($quotedEnvironment, implode(PHP_EOL, [
        'APP_NAME="Quoted Meulah"',
        'APP_ENV="production"',
        'APP_DEBUG="false"',
        'APP_TIMEZONE="Africa/Lagos"',
        'HTTP_MAX_BODY_SIZE="2048"',
        'DB_DRIVER="sqlite"',
        'DB_PATH="data/database.sqlite"',
    ]) . PHP_EOL);

    withEnvironment([], static function () use ($root, $quotedEnvironment): void {
        Environment::load($quotedEnvironment);
        $settings = Repository::load($root . '/settings');

        settingsEnsure($settings->string('app.name') === 'Quoted Meulah', 'Quoted string changed.');
        settingsEnsure(!$settings->bool('app.debug'), 'Quoted boolean was not parsed strictly.');
        settingsEnsure($settings->int('http.max_body_size') === 2048, 'Quoted integer was rejected.');
    });
} finally {
    if (is_file($quotedEnvironment)) {
        unlink($quotedEnvironment);
    }
}

withEnvironment(['APP_DEBUG' => '0'], static function () use ($root): void {
    $settings = require $root . '/settings/app.php';
    settingsEnsure($settings['debug'] === false, 'Boolean zero was not accepted.');
});

withEnvironment(['APP_DEBUG' => 'yes'], static function () use ($root): void {
    expectInvalid(
        static fn (): array => require $root . '/settings/app.php',
        'An ambiguous boolean was accepted.',
    );
});

withEnvironment(['HTTP_MAX_BODY_SIZE' => '4096'], static function () use ($root): void {
    $settings = require $root . '/settings/http.php';
    settingsEnsure($settings['max_body_size'] === 4096, 'A quoted-compatible integer was rejected.');
});

foreach (['', '0', '1.5', 'ten'] as $invalidLimit) {
    withEnvironment(['HTTP_MAX_BODY_SIZE' => $invalidLimit], static function () use ($root): void {
        expectInvalid(
            static fn (): array => require $root . '/settings/http.php',
            'An invalid HTTP body limit was accepted.',
        );
    });
}

withEnvironment(
    ['DB_DRIVER' => 'mysql', 'DB_NAME' => 'meulah', 'DB_USER' => 'root'],
    static function () use ($root): void {
        $database = require $root . '/settings/database.php';
        settingsEnsure($database['driver'] === 'mysql', 'MySQL was not configured.');
        settingsEnsure($database['port'] === 3306, 'The MySQL default port is incorrect.');
        settingsEnsure($database['charset'] === 'utf8mb4', 'The MySQL charset is missing.');
    },
);

withEnvironment(
    ['DB_DRIVER' => 'postgresql', 'DB_NAME' => 'meulah', 'DB_USER' => 'postgres'],
    static function () use ($root): void {
        $database = require $root . '/settings/database.php';
        settingsEnsure($database['driver'] === 'pgsql', 'PostgreSQL was not canonicalized.');
        settingsEnsure($database['port'] === 5432, 'The PostgreSQL default port is incorrect.');
        settingsEnsure(!isset($database['charset']), 'PostgreSQL received a MySQL-only charset.');
    },
);

withEnvironment(['DB_DRIVER' => 'oracle'], static function () use ($root): void {
    expectInvalid(
        static fn (): array => require $root . '/settings/database.php',
        'An unsupported database driver was accepted.',
    );
});

foreach (['', '../outside.sqlite', 'public/database.sqlite', 'runtime/database.sqlite'] as $invalidPath) {
    withEnvironment(
        ['DB_DRIVER' => 'sqlite', 'DB_PATH' => $invalidPath],
        static function () use ($root): void {
            expectInvalid(
                static fn (): array => require $root . '/settings/database.php',
                'An invalid SQLite path was accepted.',
            );
        },
    );
}

withEnvironment(
    ['DB_DRIVER' => 'mysql', 'DB_NAME' => '', 'DB_USER' => 'root'],
    static function () use ($root): void {
        expectInvalid(
            static fn (): array => require $root . '/settings/database.php',
            'MySQL was accepted without its required database name.',
        );
    },
);

withEnvironment(['UPLOAD_PATH' => 'public/uploads'], static function () use ($root): void {
    expectInvalid(
        static fn (): array => require $root . '/settings/files.php',
        'A public upload path was accepted.',
    );
});

withEnvironment(
    ['DB_DRIVER' => 'sqlite', 'DB_PATH' => ':memory:'],
    static function () use ($root): void {
        $database = require $root . '/settings/database.php';
        $connection = Connection::fromConfig($database);
        settingsEnsure($connection->driver() === 'sqlite', 'PDO SQLite connection failed.');
    },
);

$identifier = bin2hex(random_bytes(6));
$databaseRelative = "data/first-run-{$identifier}.sqlite";
$databasePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $databaseRelative);
$migrationsRelative = "runtime/cache/first-run-migrations-{$identifier}";
$migrationsPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $migrationsRelative);
$migrationFile = $migrationsPath . DIRECTORY_SEPARATOR . '2026_01_01_000000_create_first_run_table.php';

if (!mkdir($migrationsPath, 0777, true) && !is_dir($migrationsPath)) {
    throw new RuntimeException('Unable to create the migration test directory.');
}

try {
    file_put_contents($migrationFile, <<<'PHP'
<?php

declare(strict_types=1);

use Meulah\Database\Connection;
use Meulah\Database\Migration;

return new class implements Migration {
    public function up(Connection $connection): void
    {
        $connection->execute('CREATE TABLE first_run_test (id INTEGER PRIMARY KEY)');
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE first_run_test');
    }
};
PHP
    );

    $environment = [
        'APP_ENV' => 'development',
        'APP_DEBUG' => 'false',
        'DB_DRIVER' => 'sqlite',
        'DB_PATH' => $databaseRelative,
    ];
    $pathOption = '--path=' . $migrationsRelative;

    foreach ([
        ['migrate:status', $pathOption],
        ['migrate', $pathOption],
        ['migrate', $pathOption],
        ['migrate:status', $pathOption],
    ] as $arguments) {
        [$exitCode, $stdout, $stderr] = runSettingsProcess(
            [PHP_BINARY, $root . '/meulah', ...$arguments],
            $root,
            $environment,
        );

        settingsEnsure(
            $exitCode === 0,
            'Migration command failed: ' . $stdout . $stderr,
        );
    }

    settingsEnsure(is_file($databasePath), 'The first-run SQLite database was not created.');
    $pdo = new PDO('sqlite:' . $databasePath);
    $table = $pdo->query(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'first_run_test'",
    )->fetchColumn();
    $historyCount = $pdo->query('SELECT COUNT(*) FROM meulah_migrations')->fetchColumn();
    settingsEnsure($table === 'first_run_test', 'The migration was not executed.');
    settingsEnsure((int) $historyCount === 1, 'The migration was not safely repeatable.');
    $pdo = null;
} finally {
    if (is_file($migrationFile)) {
        unlink($migrationFile);
    }

    if (is_dir($migrationsPath)) {
        rmdir($migrationsPath);
    }

    if (is_file($databasePath)) {
        unlink($databasePath);
    }
}

echo "Settings and first-run database tests passed.\n";
