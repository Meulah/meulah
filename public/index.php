<?php

declare(strict_types=1);

use Meulah\Application;
use Meulah\Http\Request;
use Meulah\Http\Response;

$root = dirname(__DIR__);
$autoloader = $root . '/vendor/autoload.php';

if (!is_file($autoloader)) {
    error_log('Meulah could not load Composer dependencies.');
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>500</h1><p>Application could not start.</p>';
    return;
}

require_once $autoloader;

$app = null;
$request = null;

try {
    $app = require $root . '/start/app.php';

    if (!$app instanceof Application) {
        throw new UnexpectedValueException('The application boot file returned an invalid value.');
    }

    $request = Request::capture($app->config()->int('http.max_body_size'));
    $response = $app->handle($request);
} catch (Throwable $exception) {
    if ($app instanceof Application) {
        $response = $app->renderException($exception, $request);
    } else {
        error_log('Meulah application boot failed: ' . $exception::class);
        $response = Response::html('<h1>500</h1><p>Application could not start.</p>', 500);
    }
}

$response->send();
