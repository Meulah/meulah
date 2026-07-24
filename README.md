# Meulah application starter

Meulah is a small PHP framework for conventional server-rendered applications.
This repository gives a new application its entry point, folders, settings,
first route, controller, PHP view, migration directory, and smoke tests.

Meulah is pre-1.0 software. The starter is intentionally small and explicit; it
is a foundation to evaluate and build on, not a claim of production readiness.

## What this repository is

The two official packages have different jobs:

- [`meulah/framework`](https://github.com/Meulah/framework) is the reusable
  framework library. It provides HTTP requests and responses, routing,
  middleware, dependency injection, configuration, migrations, and the CLI.
- [`meulah/meulah`](https://github.com/Meulah/meulah) is this application
  starter. Your controllers, settings, routes, views, migrations, and data live
  here.

The starter consumes the published framework package with
`meulah/framework:^0.2`; it does not contain a local framework checkout.

## Requirements

- PHP `^8.1`. CI verifies PHP 8.1 and PHP 8.5.
- Composer 2.
- `ext-fileinfo` for server-side file type inspection.
- `ext-pdo` for database connections.
- `ext-pdo_sqlite` for the default SQLite setup.

For another database, install its PDO driver too:

- MySQL: `ext-pdo_mysql`
- PostgreSQL: `ext-pdo_pgsql`

The packaged starter requires `ext-pdo_sqlite` because SQLite is its default
first-run database. An application that permanently changes that default should
review its own Composer extension requirements deliberately.

## Installation

After the starter has a tagged stable release on Packagist, create an
application and enter its directory:

```bash
composer create-project meulah/meulah my-app
cd my-app
```

Create the local environment file with the command for your shell.

PowerShell:

```powershell
Copy-Item .env.example .env
```

Command Prompt:

```bat
copy .env.example .env
```

Linux or macOS:

```bash
cp .env.example .env
```

Review `.env` before continuing. Never commit it.

## First run

Inspect the CLI and database state, then run pending migrations:

```bash
php meulah --help
php meulah migrate:status
php meulah migrate
```

For local development, PHP's built-in server can serve the `public/` directory:

```bash
php -S localhost:8000 -t public
```

Open <http://localhost:8000>. The built-in server is for development only; use
a properly configured web server and PHP runtime in deployment.

## Directory guide

| Directory | Purpose |
| --- | --- |
| `app/` | Application code, including controllers, middleware, and services. Dependency bindings live in `app/bindings.php`. |
| `start/` | Application assembly and startup: create services, register middleware, and load routes. |
| `settings/` | Application configuration returned as plain PHP arrays. |
| `routes/` | Route declarations. Browser routes begin in `routes/web.php`. |
| `views/` | Plain PHP views. Opia may be added later, but it is not implemented or enabled in Framework 0.2. |
| `database/` | Database migrations, stored in `database/migrations/`. |
| `data/` | Persistent application-owned data: the default SQLite database and uploaded files. Back this directory up. |
| `public/` | The only directory a web server should expose. `public/index.php` is the HTTP entry point. |
| `runtime/` | Generated or regenerable cache, logs, sessions, and generated views. |
| `tests/` | Application smoke tests for startup, HTTP, settings, CLI, migrations, and distribution boundaries. |

## Request lifecycle

The starter keeps application assembly visible:

```text
Browser
  ? public/index.php
  ? start/app.php
  ? app/bindings.php
  ? start/middleware.php
  ? start/routes.php
  ? routes/web.php
  ? controller
  ? view
  ? response
```

`public/index.php` captures the request and sends the response. `start/app.php`
is the composition root: it loads the environment and settings, creates the
container, logger, exception handler, view renderer, router, and application,
then applies the three application extension points in order.

## Routes

Routes are registered in `routes/web.php` through the Framework 0.2 router. The
starter's named home route uses an invokable controller:

```php
<?php

declare(strict_types=1);

use App\Controllers\HomeController;
use Meulah\Routing\Router;

/** @var Router $router */
$router->get('/', HomeController::class, 'home');
```

The third argument is the route name. It can be used for URL generation:

```php
$url = $router->url('home'); // /
```

Framework 0.2 also provides `post()`, `put()`, `patch()`, `delete()`,
`options()`, route groups, middleware, and parameter constraints. Keep route
files declarative; move request handling into controllers.

## Controllers

Controllers belong in `app/Controllers/` under the `App\Controllers` namespace.
The container constructs controllers and resolves class-typed constructor
dependencies:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use Meulah\Config\Repository;
use Meulah\Http\Response;
use Meulah\Http\ResponseInterface;
use Meulah\View\View;

final class HomeController
{
    public function __construct(
        private readonly View $views,
        private readonly Repository $config,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        return Response::html($this->views->render('home', [
            'applicationName' => $this->config->string('app.name'),
        ]));
    }
}
```

Controllers should receive dependencies explicitly. They should not fetch the
container or rely on global application state.

## Bindings

`app/bindings.php` returns a closure that receives the application container.
Register application-owned interface implementations there:

```php
<?php

declare(strict_types=1);

use Meulah\Container\Container;

return static function (Container $container): void {
    // Application dependency bindings belong here.
};
```

The container offers three registration lifetimes. These examples are
alternatives, not three registrations to use together:

```php
use Meulah\Log\ErrorLogLogger;
use Meulah\Log\Logger;

$container->bind(Logger::class, ErrorLogLogger::class);
$container->singleton(Logger::class, ErrorLogLogger::class);
$container->instance(Logger::class, new ErrorLogLogger());
```

- `bind()` resolves a new object each time.
- `singleton()` resolves once and reuses that object within the container's
  lifetime.
- `instance()` registers an object that already exists.

Do not make request-sensitive services process-global singletons. A guard,
session, current-user service, or anything caching request data must have a
request-bounded lifetime, especially under a long-running PHP worker.

## Middleware

Global middleware is registered explicitly in `start/middleware.php` with
`$app->middleware(...)`. Each item must implement `Meulah\Http\Middleware`.

Middleware runs in registration order on the way into the application and in
reverse order while the response unwinds. Put prerequisites before consumers;
for example, session middleware must run before middleware that reads session
state. Route-specific middleware may also be attached through route groups.

The starter enables no optional middleware by default.

## Views

Views are plain PHP files under `views/`. The registered `Meulah\View\View`
renderer maps a dot-separated name such as `users.card` to
`views/users/card.php`.

PHP does not escape output automatically. Escape every untrusted or dynamic
value for its output context:

```php
<h1><?= htmlspecialchars(
    (string) $applicationName,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8',
) ?></h1>
```

Do not render untrusted content as raw HTML. HTML text, attributes, URLs,
JavaScript, and CSS have different escaping requirements.

Opia has a language-design RFC, but no Opia parser, compiler, runtime, or view
integration exists in Framework 0.2. Use PHP views today.

## Settings

Files in `settings/` return plain arrays. `start/app.php` loads them into
`Meulah\Config\Repository`, where values are read with strict helpers such as
`string()`, `int()`, and `bool()`.

`.env.example` documents the starter's supported environment values. Copy it to
`.env` for local settings. The real `.env` is ignored by Git and must never be
committed, included in a web response, or exposed by the web server. Deployment
secrets should come from protected environment or secret-management facilities.

Settings currently cover the application, HTTP request-body limit, database,
and file paths. Paths are resolved from the project root, not the shell's current
working directory.

## Database

SQLite is the default. Its database is created at `data/database.sqlite`,
outside `public/` and outside disposable runtime storage.

Create and run a migration:

```bash
php meulah make:migration create_users_table
php meulah migrate:status
php meulah migrate
```

Framework 0.2 also supports:

```bash
php meulah migrate:rollback
php meulah migrate:reset
php meulah migrate:fresh
```

`migrate:rollback` reverses the latest batch. `migrate:reset` reverses every
migration. `migrate:fresh` drops all tables and reruns migrations. Treat these
as destructive operations and back up important data first.

To use MySQL or PostgreSQL, enable one matching block in `.env`, install the
appropriate PDO extension, and provide the database name and credentials. Do
not enable multiple driver configurations at once.

## Uploads

Persistent uploads belong in `data/uploads/`:

- the directory is outside `public/`;
- uploads survive runtime cleanup and should be backed up;
- original filenames, extensions, and client-provided MIME types are untrusted;
- applications should generate server-side names, enforce size limits, inspect
  detected content, and prevent path traversal and executable content; and
- private files should be returned only through authorized download routes.

Do not create a public symlink to the upload directory and do not store
permanent uploads in `runtime/`.

## Runtime

`runtime/` is reserved for generated cache, logs, sessions, and generated view
files. Its contents are ignored by Git and may be regenerated, but clearing it
can remove active sessions, diagnostic logs, cached values, and compiled views.
Stop relevant application processes before cleanup.

Runtime cleanup must never remove `data/`. The SQLite database and uploads are
persistent application data with a separate backup and retention policy.

## CLI

The root `meulah` launcher resolves Composer relative to its own file, so it is
independent of the shell's working directory. Verified Framework 0.2 commands:

```text
php meulah --help
php meulah --version
php meulah list
php meulah help migrate
php meulah make:migration create_users_table
php meulah migrate
php meulah migrate:status
php meulah migrate:rollback
php meulah migrate:reset
php meulah migrate:fresh
```

Global help and version output support explicit color control:

```bash
php meulah --help --ansi
php meulah --help --no-ansi
```

`--ansi` forces ANSI styling and `--no-ansi` disables it. Setting the standard
`NO_COLOR` environment variable also disables automatic color. In Framework
0.2 these color switches accompany global `--help` or `--version`; they are not
migration-command options. Redirected and non-interactive output remains plain
unless color is explicitly forced.

## Testing

The starter has dependency-free application smoke tests:

```bash
composer lint
composer test
composer check
```

They lint application PHP and test startup, settings, the home request,
constructor injection, views, CLI behavior, SQLite migrations, rollback,
working-directory independence, and distribution boundaries. They consume the
published framework package rather than duplicating its unit tests.

## Deployment

Adapt deployment to your infrastructure and threat model. At minimum:

- point the web-server document root to `public/`, never the repository root;
- set `APP_ENV=production` and `APP_DEBUG=false`;
- keep `.env` and other secrets outside public access;
- require HTTPS and configure secure transport at the web-server or proxy layer;
- grant the PHP process only the runtime and data permissions it needs;
- prevent execution of uploaded content and keep `data/` outside public access;
- keep logs under `runtime/logs/` and outside public access;
- back up `data/database.sqlite` and `data/uploads/` when using them;
- deploy reviewed migrations deliberately; and
- install production dependencies with:

```bash
composer install --no-dev --classmap-authoritative --prefer-dist --no-interaction
```

Production operation still requires application-specific security review,
monitoring, backups, recovery testing, web-server hardening, and dependency
maintenance.

## Stability

Meulah is pre-1.0. Framework 0.2 is suitable for evaluation and application
development, but minor releases may make breaking changes while the public API
is refined. Pin compatible constraints, commit `composer.lock`, read release
notes, and test upgrades before deployment.

## Security

Read [SECURITY.md](SECURITY.md) for supported versions, private vulnerability
reporting, coordinated disclosure, and upload-safety guidance. Do not report a
suspected vulnerability in a public issue.

## Repository links

- Framework: <https://github.com/Meulah/framework>
- Application starter: <https://github.com/Meulah/meulah>
