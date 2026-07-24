<?php

declare(strict_types=1);

use Meulah\Support\Environment;

$value = Environment::get('HTTP_MAX_BODY_SIZE', 10_485_760);

if (is_int($value)) {
    $maxBodySize = $value;
} elseif (is_string($value) && preg_match('/^[1-9][0-9]*$/', $value) === 1) {
    $validated = filter_var($value, FILTER_VALIDATE_INT);
    $maxBodySize = $validated === false ? 0 : $validated;
} else {
    throw new InvalidArgumentException('HTTP_MAX_BODY_SIZE must be a positive integer.');
}

if ($maxBodySize < 1) {
    throw new InvalidArgumentException('HTTP_MAX_BODY_SIZE must be a positive integer.');
}

return [
    'max_body_size' => $maxBodySize,
];
