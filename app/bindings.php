<?php

declare(strict_types=1);

use Meulah\Container\Container;
use Meulah\Log\ErrorLogLogger;
use Meulah\Log\Logger;
use Meulah\View\View;

return static function (Container $container): void {
    $root = dirname(__DIR__);

    $container->singleton(Logger::class, ErrorLogLogger::class);
    $container->singleton(
        View::class,
        static fn (): View => new View($root . '/views'),
    );
};
