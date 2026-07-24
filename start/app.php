<?php

declare(strict_types=1);

use Meulah\Application;
use Meulah\Config\Repository;
use Meulah\Container\Container;
use Meulah\Exception\ExceptionHandler;
use Meulah\Log\Logger;
use Meulah\Routing\Router;
use Meulah\Support\Environment;

$root = dirname(__DIR__);

Environment::load($root . '/.env');
$config = Repository::load($root . '/settings');
$debug = $config->bool('app.debug');

error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('error_log', $root . '/runtime/logs/meulah.log');

$container = new Container();

/** @var callable(Container): void $bindings */
$bindings = require $root . '/app/bindings.php';
$bindings($container);

$app = new Application(
    new Router($container),
    $config,
    new ExceptionHandler($debug, $container->get(Logger::class)),
);

/** @var callable(Application): void $middleware */
$middleware = require $root . '/start/middleware.php';
$middleware($app);

return $app;
