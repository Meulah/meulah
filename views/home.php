<?php

declare(strict_types=1);

/** @var string $applicationName */
$escape = static fn (string $value): string => htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8',
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title><?= $escape($applicationName) ?> - Meulah</title>
    <link rel="stylesheet" href="/assets/css/welcome.css">
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <main class="welcome" id="main-content">
        <section class="welcome-card" aria-labelledby="welcome-title">
            <img
                class="logo"
                src="/assets/images/meulah-logo.png"
                alt="Meulah"
                width="732"
                height="171"
            >

            <p class="status">
                <span class="status-dot" aria-hidden="true"></span>
                Application running
            </p>

            <h1 id="welcome-title">Meulah is ready.</h1>
            <p class="intro">
                <strong><?= $escape($applicationName) ?></strong> has booted successfully.
                Edit <code>routes/web.php</code> to start building.
            </p>

            <div class="commands" aria-label="Useful first commands">
                <article class="command">
                    <span>Explore the CLI</span>
                    <code>php meulah --help</code>
                </article>
                <article class="command">
                    <span>Check the database</span>
                    <code>php meulah migrate:status</code>
                </article>
            </div>

            <nav class="links" aria-label="Meulah repositories">
                <a class="primary-link" href="https://github.com/Meulah/framework">Framework repository</a>
                <a href="https://github.com/Meulah/meulah">Starter repository</a>
            </nav>
        </section>
    </main>

    <footer class="site-footer">
        <span>Framework 0.2</span>
        <span>Plain PHP views. No frontend build step.</span>
    </footer>
</body>
</html>