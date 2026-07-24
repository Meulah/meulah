<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use Meulah\Application;
use Meulah\Config\Repository;
use Meulah\Http\Request;
use Meulah\Http\ResponseInterface;
use Meulah\View\View;

$root = dirname(__DIR__);

require_once $root . '/vendor/autoload.php';

function homeEnsure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$app = require $root . '/start/app.php';
homeEnsure($app instanceof Application, 'The starter did not boot a Meulah application.');
homeEnsure($app->router()->url('home') === '/', 'The home route name does not generate /.');

$controllerReflection = new ReflectionClass(HomeController::class);
homeEnsure($controllerReflection->isFinal(), 'HomeController must remain final.');
$constructor = $controllerReflection->getConstructor();
homeEnsure($constructor instanceof ReflectionMethod, 'HomeController has no constructor.');
$parameters = $constructor->getParameters();
homeEnsure(count($parameters) === 2, 'HomeController must have two explicit dependencies.');
homeEnsure(
    $parameters[0]->getType() instanceof ReflectionNamedType
        && $parameters[0]->getType()->getName() === View::class,
    'HomeController does not inject View.',
);
homeEnsure(
    $parameters[1]->getType() instanceof ReflectionNamedType
        && $parameters[1]->getType()->getName() === Repository::class,
    'HomeController does not inject Repository.',
);

$controller = $app->container()->get(HomeController::class);
homeEnsure($controller instanceof HomeController, 'The container did not resolve HomeController.');

$response = $app->handle(new Request('GET', '/'));
homeEnsure($response instanceof ResponseInterface, 'GET / did not return the response contract.');
homeEnsure($response->status() === 200, 'GET / did not return HTTP 200.');
homeEnsure(
    ($response->headers()['Content-Type'] ?? null) === 'text/html; charset=UTF-8',
    'GET / did not return an HTML response.',
);

$html = $response->content();

foreach ([
    'Meulah is running.',
    'Request lifecycle',
    'Three verified next steps',
    'Persistent application-owned files.',
    'Generated or regenerable runtime files.',
    'not a claim that an application is production-ready',
    'not implemented or enabled in Framework 0.2',
] as $expectedText) {
    homeEnsure(str_contains($html, $expectedText), "Welcome page omitted: {$expectedText}");
}

foreach ([
    'app/',
    'start/',
    'settings/',
    'routes/',
    'views/',
    'database/',
    'data/uploads/',
    'runtime/',
    'public/',
] as $folder) {
    homeEnsure(str_contains($html, $folder), "Welcome page omitted folder: {$folder}");
}

$lifecycle = [
    'public/index.php',
    'start/app.php',
    'app/bindings.php',
    'start/middleware.php',
    'start/routes.php',
    'routes/web.php',
    'HomeController',
    'views/home.php',
    'Response',
];
$previousPosition = -1;

foreach ($lifecycle as $stage) {
    $position = strpos($html, $stage, $previousPosition + 1);
    homeEnsure(is_int($position), "Request lifecycle omitted: {$stage}");
    homeEnsure($position > $previousPosition, 'Request lifecycle is out of order.');
    $previousPosition = $position;
}

foreach ([
    'https://github.com/Meulah/framework',
    'https://github.com/Meulah/meulah',
] as $repositoryUrl) {
    homeEnsure(str_contains($html, 'href="' . $repositoryUrl . '"'), "Missing link: {$repositoryUrl}");
}

preg_match_all('/<code class="command">([^<]+)<\/code>/', $html, $commandMatches);
$commands = array_map(
    static fn (string $command): string => html_entity_decode($command, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    $commandMatches[1] ?? [],
);
homeEnsure(
    $commands === ['php meulah --help', 'php meulah migrate:status'],
    'The welcome page displays unverified or missing commands.',
);
homeEnsure(!str_contains($html, 'php meulah serve'), 'The welcome page advertises an unsupported serve command.');
homeEnsure(!str_contains($html, 'make:controller'), 'The welcome page advertises an unsupported generator.');

homeEnsure(stripos($html, '<script') === false, 'The welcome page contains JavaScript.');
homeEnsure(
    preg_match(
        '/<(?:link|script|img|iframe|source|video|audio)\b[^>]*(?:href|src)\s*=\s*["\'](?:https?:)?\/\//i',
        $html,
    ) !== 1,
    'The welcome page loads a remote asset.',
);
homeEnsure(
    preg_match('/url\(\s*["\']?https?:\/\//i', $html) !== 1,
    'The welcome page CSS loads a remote asset.',
);

foreach ([
    '<html lang="en">',
    '<meta name="viewport"',
    'class="skip-link"',
    'href="#main-content"',
    '<main id="main-content">',
    ':focus-visible',
] as $accessibilityMarker) {
    homeEnsure(str_contains($html, $accessibilityMarker), "Accessibility marker missing: {$accessibilityMarker}");
}

$hostileName = '<img src=x onerror=alert(1)>';
$hostileController = new HomeController(
    new View($root . '/views'),
    new Repository(['app' => ['name' => $hostileName]]),
);
$escapedHtml = $hostileController()->content();
homeEnsure(!str_contains($escapedHtml, $hostileName), 'Dynamic application name was not escaped.');
homeEnsure(
    str_contains($escapedHtml, '&lt;img src=x onerror=alert(1)&gt;'),
    'Escaped application name is missing.',
);

$controllerSource = file_get_contents($root . '/app/Controllers/HomeController.php');
homeEnsure(is_string($controllerSource), 'Unable to inspect HomeController.');
homeEnsure(!str_contains($controllerSource, 'container('), 'HomeController performs a container lookup.');
homeEnsure(!str_contains($controllerSource, 'Facade'), 'HomeController uses a facade.');

$opiaFiles = [];
$viewFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/views', FilesystemIterator::SKIP_DOTS),
);

foreach ($viewFiles as $file) {
    if ($file->isFile() && strtolower($file->getExtension()) === 'opia') {
        $opiaFiles[] = $file->getPathname();
    }
}

homeEnsure($opiaFiles === [], 'The starter contains an Opia template.');

echo "Home route and welcome page tests passed.\n";
