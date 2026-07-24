<?php

declare(strict_types=1);

use Meulah\Support\Environment;

$readString = static function (string $key, string $default): string {
    $value = Environment::get($key, $default);

    if (
        !is_string($value)
        || $value === ''
        || $value !== trim($value)
        || preg_match('/[\x00-\x1F\x7F]/', $value) === 1
    ) {
        throw new InvalidArgumentException("{$key} must be a non-empty string without surrounding whitespace.");
    }

    return $value;
};

$readBoolean = static function (string $key, bool $default): bool {
    $value = Environment::get($key, $default);

    if (is_bool($value)) {
        return $value;
    }

    if ($value === 1 || $value === '1' || $value === 'true') {
        return true;
    }

    if ($value === 0 || $value === '0' || $value === 'false') {
        return false;
    }

    throw new InvalidArgumentException("{$key} must be true, false, 1, or 0.");
};

$environment = strtolower($readString('APP_ENV', 'production'));

if (preg_match('/^[a-z][a-z0-9_-]*$/', $environment) !== 1) {
    throw new InvalidArgumentException('APP_ENV contains unsupported characters.');
}

$timezone = $readString('APP_TIMEZONE', 'UTC');

if (!in_array($timezone, timezone_identifiers_list(), true)) {
    throw new InvalidArgumentException('APP_TIMEZONE must be a recognized PHP timezone identifier.');
}

return [
    'name' => $readString('APP_NAME', 'Meulah'),
    'environment' => $environment,
    'debug' => $readBoolean('APP_DEBUG', $environment === 'development'),
    'timezone' => $timezone,
];
