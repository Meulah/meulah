<?php

declare(strict_types=1);

use Meulah\Application;
use Meulah\Config\Repository;
use Meulah\Container\Container;
use Meulah\Exception\ExceptionHandler;
use Meulah\Http\Request;
use Meulah\Log\Logger;
use Meulah\Routing\Router;
use Meulah\View\View;

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

function ensure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * @param list<string> $command
 * @return array{int, string, string}
 */
function runProcess(array $command, string $workingDirectory): array
{
    $process = proc_open(
        $command,
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
        $workingDirectory,
    );

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start a PHP test process.');
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

function removeTree(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($items as $item) {
        if ($item->isDir() && !$item->isLink()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($directory);
}

if (($argv[1] ?? null) === 'probe') {
    $app = require $root . '/start/app.php';
    ensure($app instanceof Application, 'start/app.php did not return the application.');

    $response = $app->handle(new Request('GET', '/'));
    ensure($response->status() === 200, 'The home route did not return HTTP 200.');
    ensure(str_contains($response->content(), 'Meulah'), 'The home view was not rendered.');

    echo "probe-ok\n";
    exit(0);
}

$temporaryWorkingDirectory = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'meulah startup cwd '
    . bin2hex(random_bytes(6));

if (!mkdir($temporaryWorkingDirectory, 0777, true) && !is_dir($temporaryWorkingDirectory)) {
    throw new RuntimeException('Unable to create the startup test directory.');
}

try {
    $workingDirectories = [
        $root,
        dirname($root),
        $root . '/app/Controllers',
        $temporaryWorkingDirectory,
    ];

    foreach ($workingDirectories as $workingDirectory) {
        [$exitCode, $stdout, $stderr] = runProcess(
            [PHP_BINARY, __FILE__, 'probe'],
            $workingDirectory,
        );

        ensure(
            $exitCode === 0 && str_contains($stdout, 'probe-ok'),
            "Startup failed from {$workingDirectory}: {$stderr}",
        );
    }
} finally {
    removeTree($temporaryWorkingDirectory);
}

$app = require $root . '/start/app.php';
ensure($app instanceof Application, 'The composition root did not return an application.');

$container = $app->container();
ensure($container->get(Repository::class) === $app->config(), 'Settings were not registered.');
ensure($container->get(Logger::class) instanceof Logger, 'The logger was not registered.');
ensure(
    $container->get(ExceptionHandler::class) instanceof ExceptionHandler,
    'The exception handler was not registered.',
);
ensure($container->get(View::class) instanceof View, 'The PHP view renderer was not registered.');
ensure($container->get(Router::class) === $app->router(), 'The application router was not registered.');

$bindings = require $root . '/app/bindings.php';
ensure($bindings instanceof Closure, 'app/bindings.php must return a closure.');
$bindings(new Container());

$middleware = require $root . '/start/middleware.php';
ensure($middleware instanceof Closure, 'start/middleware.php must return a closure.');
$middleware(new Application(new Router(new Container())));

$routes = require $root . '/start/routes.php';
ensure($routes instanceof Closure, 'start/routes.php must return a closure.');
$routeTestRouter = new Router(new Container());
$routes($routeTestRouter);
ensure($routeTestRouter->url('home') === '/', 'routes/web.php was not loaded explicitly.');

$response = $app->handle(new Request('GET', '/'));
ensure($response->status() === 200, 'The booted application did not serve the home route.');
ensure(str_contains($response->content(), 'Meulah'), 'The booted application did not load the view.');

$indexSource = file_get_contents($root . '/public/index.php');
$appSource = file_get_contents($root . '/start/app.php');
ensure(is_string($indexSource) && is_string($appSource), 'Unable to inspect startup sources.');
ensure(substr_count($indexSource, "'/start/app.php'") === 1, 'public/index.php must boot once.');
ensure(!str_contains($indexSource, 'start/routes.php'), 'Routes must be loaded by start/app.php.');

$bindingsPosition = strpos($appSource, "'/app/bindings.php'");
$middlewarePosition = strpos($appSource, "'/start/middleware.php'");
$routesPosition = strpos($appSource, "'/start/routes.php'");
ensure(
    is_int($bindingsPosition)
        && is_int($middlewarePosition)
        && is_int($routesPosition)
        && $bindingsPosition < $middlewarePosition
        && $middlewarePosition < $routesPosition,
    'The composition-root extension points are not ordered correctly.',
);

$fixtureRoot = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'meulah broken boot '
    . bin2hex(random_bytes(6));
$secret = 'database-password-must-not-leak';

try {
    foreach (['public', 'start', 'vendor'] as $directory) {
        $path = $fixtureRoot . DIRECTORY_SEPARATOR . $directory;

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new RuntimeException("Unable to create fixture directory: {$directory}");
        }
    }

    copy($root . '/public/index.php', $fixtureRoot . '/public/index.php');
    file_put_contents(
        $fixtureRoot . '/vendor/autoload.php',
        "<?php\nrequire " . var_export($root . '/vendor/autoload.php', true) . ";\n",
    );
    file_put_contents(
        $fixtureRoot . '/start/app.php',
        "<?php\nthrow new RuntimeException(" . var_export($secret, true) . ");\n",
    );

    [$exitCode, $stdout, $stderr] = runProcess(
        [PHP_BINARY, $fixtureRoot . '/public/index.php'],
        $fixtureRoot,
    );
    $combinedOutput = $stdout . $stderr;

    ensure($exitCode === 0, 'The boot exception boundary returned a failing process.');
    ensure(str_contains($stdout, 'Application could not start.'), 'The safe boot response was absent.');
    ensure(!str_contains($combinedOutput, $secret), 'The boot response exposed a secret.');
    ensure(!str_contains($combinedOutput, $fixtureRoot), 'The boot response exposed a local path.');
} finally {
    removeTree($fixtureRoot);
}

echo "Startup lifecycle tests passed.\n";
