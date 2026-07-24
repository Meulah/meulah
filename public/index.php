<?php

declare(strict_types=1);

use Meulah\Http\Request;

$root = dirname(__DIR__);
$autoloader = $root . '/vendor/autoload.php';

if (!is_file($autoloader)) {
    throw new RuntimeException('Composer dependencies are missing. Run composer install.');
}

require_once $autoloader;

/** @var Meulah\Application $app */
$app = require $root . '/start/app.php';

/** @var callable(Meulah\Routing\Router): void $routes */
$routes = require $root . '/start/routes.php';
$routes($app->router());

try {
    $request = Request::capture($app->config()->int('http.max_body_size'));
    $app->handle($request)->send();
} catch (Throwable $exception) {
    $app->renderException($exception, $request ?? null)->send();
}
