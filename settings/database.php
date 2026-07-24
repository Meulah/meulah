<?php

declare(strict_types=1);

use Meulah\Support\Environment;

$root = dirname(__DIR__);

$readString = static function (string $key, string $default = '', bool $allowEmpty = false): string {
    $value = Environment::get($key, $default);

    if (!is_string($value)) {
        throw new InvalidArgumentException("{$key} must be a string.");
    }

    if (!$allowEmpty && $value === '') {
        throw new InvalidArgumentException("{$key} must not be empty.");
    }

    if ($value !== trim($value) || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
        throw new InvalidArgumentException("{$key} contains invalid whitespace or control characters.");
    }

    return $value;
};

$readPort = static function (string $key, int $default): int {
    $value = Environment::get($key, $default);

    if (is_int($value)) {
        $port = $value;
    } elseif (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1) {
        $validated = filter_var($value, FILTER_VALIDATE_INT);
        $port = $validated === false ? 0 : $validated;
    } else {
        throw new InvalidArgumentException("{$key} must be an integer between 1 and 65535.");
    }

    if ($port < 1 || $port > 65535) {
        throw new InvalidArgumentException("{$key} must be an integer between 1 and 65535.");
    }

    return $port;
};

$resolvePath = static function (string $key, string $default, bool $allowMemory = false) use ($root): string {
    $value = Environment::get($key, $default);

    if (!is_string($value) || $value === '') {
        throw new InvalidArgumentException("{$key} must be a non-empty path.");
    }

    if ($allowMemory && $value === ':memory:') {
        return $value;
    }

    if (
        $value !== trim($value)
        || str_ends_with($value, '/')
        || str_ends_with($value, '\\')
        || preg_match('/[\x00-\x1F\x7F]/', $value) === 1
    ) {
        throw new InvalidArgumentException("{$key} is not a valid file or directory path.");
    }

    $portable = str_replace('\\', '/', $value);

    if (in_array('..', explode('/', $portable), true)) {
        throw new InvalidArgumentException("{$key} must not traverse parent directories.");
    }

    $absolute = preg_match('#^(?:[A-Za-z]:/|/|//)#', $portable) === 1;
    $resolved = $absolute ? $portable : $root . '/' . ltrim($portable, '/');

    return str_replace('/', DIRECTORY_SEPARATOR, $resolved);
};

$driverValue = Environment::get('DB_DRIVER', 'sqlite');

if (!is_string($driverValue) || $driverValue === '' || $driverValue !== trim($driverValue)) {
    throw new InvalidArgumentException('DB_DRIVER must be a non-empty string.');
}

$driver = match (strtolower($driverValue)) {
    'sqlite' => 'sqlite',
    'mysql' => 'mysql',
    'pgsql', 'postgres', 'postgresql' => 'pgsql',
    default => throw new InvalidArgumentException(
        'DB_DRIVER must be sqlite, mysql, or pgsql.',
    ),
};

$migrations = $resolvePath('DB_MIGRATIONS', 'database/migrations');

if (!is_dir($migrations)) {
    throw new InvalidArgumentException('DB_MIGRATIONS must reference an existing directory.');
}

$migrationTable = $readString('DB_MIGRATION_TABLE', 'meulah_migrations');

if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $migrationTable) !== 1) {
    throw new InvalidArgumentException('DB_MIGRATION_TABLE must be a valid SQL identifier.');
}

$config = [
    'driver' => $driver,
    'migrations' => $migrations,
    'migration_table' => $migrationTable,
];

if ($driver === 'sqlite') {
    $path = $resolvePath('DB_PATH', 'data/database.sqlite', true);

    if ($path !== ':memory:') {
        $normalizedPath = str_replace('\\', '/', $path);
        $normalizedRoot = str_replace('\\', '/', $root);
        $comparePath = DIRECTORY_SEPARATOR === '\\' ? strtolower($normalizedPath) : $normalizedPath;
        $compareRoot = DIRECTORY_SEPARATOR === '\\' ? strtolower($normalizedRoot) : $normalizedRoot;

        foreach (['public', 'runtime'] as $forbiddenDirectory) {
            $forbidden = $compareRoot . '/' . $forbiddenDirectory;

            if ($comparePath === $forbidden || str_starts_with($comparePath, $forbidden . '/')) {
                throw new InvalidArgumentException('DB_PATH must remain outside public/ and runtime/.');
            }
        }

        if (!is_dir(dirname($path))) {
            throw new InvalidArgumentException('The parent directory configured by DB_PATH does not exist.');
        }
    }

    return $config + ['path' => $path];
}

$defaultPort = $driver === 'pgsql' ? 5432 : 3306;

$server = [
    'host' => $readString('DB_HOST', '127.0.0.1'),
    'port' => $readPort('DB_PORT', $defaultPort),
    'database' => $readString('DB_NAME'),
    'username' => $readString('DB_USER'),
    'password' => $readString('DB_PASS', '', true),
];

if ($driver === 'mysql') {
    $server['charset'] = 'utf8mb4';
}

return $config + $server;
