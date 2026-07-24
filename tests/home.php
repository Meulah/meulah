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
    'Meulah is ready.',
    'Application running',
    'has booted successfully.',
    'Edit <code>routes/web.php</code> to start building.',
    'Framework 0.2',
    'Plain PHP views. No frontend build step.',
] as $expectedText) {
    homeEnsure(str_contains($html, $expectedText), "Welcome page omitted: {$expectedText}");
}

foreach ([
    'href="/assets/css/welcome.css"',
    'src="/assets/images/meulah-logo.png"',
    'alt="Meulah"',
    'width="732"',
    'height="171"',
] as $assetMarker) {
    homeEnsure(str_contains($html, $assetMarker), "Welcome asset marker missing: {$assetMarker}");
}

foreach ([
    'https://github.com/Meulah/framework',
    'https://github.com/Meulah/meulah',
] as $repositoryUrl) {
    homeEnsure(str_contains($html, 'href="' . $repositoryUrl . '"'), "Missing link: {$repositoryUrl}");
}

foreach (['php meulah --help', 'php meulah migrate:status'] as $command) {
    homeEnsure(substr_count($html, $command) === 1, "Welcome command missing or duplicated: {$command}");
}

homeEnsure(stripos($html, '<style') === false, 'The welcome page contains inline CSS.');
homeEnsure(stripos($html, ' style=') === false, 'The welcome page contains inline style attributes.');
homeEnsure(stripos($html, '<script') === false, 'The welcome page contains JavaScript.');
homeEnsure(
    preg_match('/<(?:link|script|img)\b[^>]*(?:href|src)\s*=\s*["\'](?:https?:)?\/\//i', $html) !== 1,
    'The welcome page loads a remote asset.',
);

foreach ([
    '<html lang="en">',
    '<meta name="viewport"',
    'class="skip-link"',
    'href="#main-content"',
    '<main class="welcome" id="main-content">',
    'aria-labelledby="welcome-title"',
    'aria-label="Useful first commands"',
    'aria-label="Meulah repositories"',
] as $marker) {
    homeEnsure(str_contains($html, $marker), "Accessibility marker missing: {$marker}");
}

foreach (['Request lifecycle', 'Know where things belong', 'Three verified next steps', 'Opia'] as $removed) {
    homeEnsure(!str_contains($html, $removed), "Busy welcome section remains: {$removed}");
}

$stylesheet = file_get_contents($root . '/public/assets/css/welcome.css');
homeEnsure(is_string($stylesheet), 'The welcome stylesheet is missing.');
homeEnsure(str_contains($stylesheet, ':focus-visible'), 'The stylesheet has no visible focus treatment.');
homeEnsure(str_contains($stylesheet, '@media (max-width: 38rem)'), 'The stylesheet has no responsive rule.');
homeEnsure(str_contains($stylesheet, '@media (prefers-reduced-motion: reduce)'), 'Reduced motion is unsupported.');
homeEnsure(preg_match('/https?:\/\//i', $stylesheet) !== 1, 'The stylesheet loads a remote asset.');

$logoInfo = getimagesize($root . '/public/assets/images/meulah-logo.png');
homeEnsure(is_array($logoInfo), 'The Meulah logo is missing or invalid.');
homeEnsure(($logoInfo[0] ?? null) === 732 && ($logoInfo[1] ?? null) === 171, 'The Meulah logo dimensions changed.');
homeEnsure(($logoInfo['mime'] ?? null) === 'image/png', 'The Meulah logo is not PNG.');

$hostileName = '<img src=x onerror=alert(1)>';
$hostileController = new HomeController(
    new View($root . '/views'),
    new Repository(['app' => ['name' => $hostileName]]),
);
$escapedHtml = $hostileController()->content();
homeEnsure(!str_contains($escapedHtml, $hostileName), 'Dynamic application name was not escaped.');
homeEnsure(str_contains($escapedHtml, '&lt;img src=x onerror=alert(1)&gt;'), 'Escaped name is missing.');

$controllerSource = file_get_contents($root . '/app/Controllers/HomeController.php');
homeEnsure(is_string($controllerSource), 'Unable to inspect HomeController.');
homeEnsure(!str_contains($controllerSource, 'container('), 'HomeController performs a container lookup.');
homeEnsure(!str_contains($controllerSource, 'Facade'), 'HomeController uses a facade.');

echo "Home route and welcome page tests passed.\n";