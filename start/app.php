<?php

declare(strict_types=1);

use Meulah\Application;
use Meulah\Config\Repository;
use Meulah\Container\Container;
use Meulah\Exception\ExceptionHandler;
use Meulah\Log\ErrorLogLogger;
use Meulah\Log\Logger;
use Meulah\Routing\Router;
use Meulah\Support\Environment;
use Meulah\View\View;

$root = dirname(__DIR__);

Environment::load($root . '/.env');
$config = Repository::load($root . '/settings');
$debug = $config->bool('app.debug');

date_default_timezone_set($config->string('app.timezone'));
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('error_log', $config->string('files.logs') . DIRECTORY_SEPARATOR . 'meulah.log');

$container = new Container();
$container->instance(Repository::class, $config);

$logger = new ErrorLogLogger();
$container->instance(Logger::class, $logger);

$exceptions = new ExceptionHandler($debug, $logger);
$container->instance(ExceptionHandler::class, $exceptions);

$container->singleton(
    View::class,
    static fn (Container $_container): View => new View($root . '/views'),
);

$router = new Router($container);
$app = new Application($router, $config, $exceptions);

/** @var callable(Container): void $bindings */
$bindings = require $root . '/app/bindings.php';
$bindings($container);

/** @var callable(Application): void $middleware */
$middleware = require $root . '/start/middleware.php';
$middleware($app);

/** @var callable(Router): void $routes */
$routes = require $root . '/start/routes.php';
$routes($router);

return $app;
