<?php

declare(strict_types=1);

use Meulah\Support\Environment;

$root = dirname(__DIR__);

$directory = static function (string $key, string $default, string $boundary) use ($root): string {
    $value = Environment::get($key, $default);

    if (
        !is_string($value)
        || $value === ''
        || $value !== trim($value)
        || preg_match('/[\x00-\x1F\x7F]/', $value) === 1
    ) {
        throw new InvalidArgumentException("{$key} must be a non-empty relative directory path.");
    }

    $portable = str_replace('\\', '/', $value);

    if (
        preg_match('#^(?:[A-Za-z]:/|/|//)#', $portable) === 1
        || in_array('..', explode('/', $portable), true)
        || str_ends_with($portable, '/')
    ) {
        throw new InvalidArgumentException("{$key} must be a project-relative directory without traversal.");
    }

    if ($portable !== $boundary && !str_starts_with($portable, $boundary . '/')) {
        throw new InvalidArgumentException("{$key} must remain under {$boundary}/.");
    }

    $resolved = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $portable);

    if (!is_dir($resolved)) {
        throw new InvalidArgumentException("{$key} must reference an existing directory.");
    }

    return $resolved;
};

return [
    'uploads' => $directory('UPLOAD_PATH', 'data/uploads', 'data'),
    'cache' => $directory('CACHE_PATH', 'runtime/cache', 'runtime'),
    'logs' => $directory('LOG_PATH', 'runtime/logs', 'runtime'),
    'sessions' => $directory('SESSION_PATH', 'runtime/sessions', 'runtime'),
    'views' => $directory('VIEW_CACHE_PATH', 'runtime/views', 'runtime'),
];
