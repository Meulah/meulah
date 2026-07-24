# Meulah application

This is the minimal application starter for the Meulah framework. It is intended to be installed with:

```bash
composer create-project meulah/meulah my-app
```

## Start

1. Copy `.env.example` to `.env` and configure the application.
2. Point the web server document root to `public/`.

SQLite is the first-run database and stores its file at `data/database.sqlite`.
The PHP PDO SQLite extension must be enabled. MySQL and PostgreSQL examples are
available in `.env.example`; enable one database configuration at a time.

The application starts in `start/app.php`. Application code belongs in `app/`,
container bindings in `app/bindings.php`, routes in `routes/`, views in
`views/`, settings in `settings/`, and migrations in `database/migrations/`.
The web server must expose only `public/`.
Generated files in `runtime/` may be cleared; persistent uploads belong in
`data/uploads/` and are deliberately kept outside `public/`.

Use the root launcher for framework commands:

```bash
php meulah --help
php meulah --version
php meulah make:migration create_users_table
php meulah migrate
php meulah migrate:status
```

## Runtime, data, and uploads

`runtime/` contains disposable cache, log, session, and generated-view data.
Stop any running application processes before clearing it. `data/` contains
persistent application-owned data and must be included in an appropriate backup
plan. Never store permanent uploads in `runtime/`.

Treat every upload as untrusted: generate server-side filenames, enforce size
limits, inspect detected MIME content, prevent path traversal and executable
content, and authorize access to private downloads. See [SECURITY.md](SECURITY.md)
for the reporting policy and complete upload guidance.
