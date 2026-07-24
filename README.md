# Meulah application

This is the minimal application starter for the Meulah framework. It is intended to be installed with:

```bash
composer create-project meulah/meulah my-app
```

## Start

1. Copy `.env.example` to `.env` and configure the application.
2. Point the web server document root to `public/`.

The application starts in `start/app.php`. Application code belongs in `app/`,
container bindings in `app/bindings.php`, routes in `routes/`, views in `views/`,
settings in `settings/`, and migrations in `database/migrations/`.
Generated files in `runtime/` may be cleared; persistent uploads belong in
`data/uploads/` and are deliberately kept outside `public/`.

Use the root launcher for framework commands:

```bash
php meulah make:migration create_users_table
php meulah migrate
php meulah migrate:status
```
