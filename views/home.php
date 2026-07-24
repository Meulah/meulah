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
    <meta name="color-scheme" content="dark">
    <title><?= $escape($applicationName) ?> ? Meulah is running</title>
    <style>
        :root {
            color-scheme: dark;
            --background: #07111f;
            --surface: #0e1b2d;
            --surface-raised: #14243a;
            --border: #2a405d;
            --text: #f5f7fb;
            --muted: #b8c5d8;
            --accent: #55d6be;
            --accent-strong: #8aead8;
            --warm: #ffc76a;
            --focus: #ffdf8a;
            --max-width: 72rem;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-width: 20rem;
            margin: 0;
            color: var(--text);
            background:
                radial-gradient(circle at 15% -5%, #173a54 0, transparent 32rem),
                var(--background);
            line-height: 1.65;
        }

        a {
            color: var(--accent-strong);
            text-underline-offset: 0.2em;
        }

        a:hover {
            color: #ffffff;
        }

        :focus-visible {
            border-radius: 0.2rem;
            outline: 0.2rem solid var(--focus);
            outline-offset: 0.2rem;
        }

        code {
            color: #dcfff8;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", monospace;
            overflow-wrap: anywhere;
        }

        .skip-link {
            position: fixed;
            z-index: 10;
            top: 0.75rem;
            left: 0.75rem;
            padding: 0.65rem 0.9rem;
            color: #07111f;
            background: var(--focus);
            font-weight: 800;
            transform: translateY(-180%);
        }

        .skip-link:focus {
            transform: translateY(0);
        }

        .site-header,
        main,
        .site-footer {
            width: min(calc(100% - 2rem), var(--max-width));
            margin-inline: auto;
        }

        .site-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-block: 1.25rem;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            color: var(--text);
            font-size: 1.05rem;
            font-weight: 800;
            text-decoration: none;
        }

        .brand-mark {
            display: grid;
            width: 2.25rem;
            height: 2.25rem;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 0.65rem;
            color: var(--background);
            background: var(--accent);
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .status::before {
            width: 0.65rem;
            height: 0.65rem;
            border-radius: 999px;
            background: var(--accent);
            content: "";
        }

        main {
            padding-block: 3.5rem 5rem;
        }

        section + section {
            margin-top: 4.5rem;
        }

        .hero {
            max-width: 56rem;
            padding-block: 3rem 2rem;
        }

        .eyebrow {
            margin: 0 0 0.8rem;
            color: var(--warm);
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        h1 {
            max-width: 13ch;
            margin-bottom: 1.25rem;
            font-size: clamp(3rem, 9vw, 6.8rem);
            line-height: 0.98;
            letter-spacing: -0.055em;
        }

        h2 {
            margin-bottom: 0.75rem;
            font-size: clamp(1.75rem, 4vw, 2.6rem);
            line-height: 1.15;
            letter-spacing: -0.025em;
        }

        h3 {
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .lead {
            max-width: 47rem;
            color: var(--muted);
            font-size: clamp(1.05rem, 2.4vw, 1.3rem);
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.75rem;
        }

        .button {
            display: inline-flex;
            min-height: 2.8rem;
            align-items: center;
            padding: 0.65rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.65rem;
            color: var(--text);
            background: var(--surface);
            font-weight: 750;
            text-decoration: none;
        }

        .button.primary {
            border-color: var(--accent);
            color: var(--background);
            background: var(--accent);
        }

        .button:hover {
            border-color: var(--accent-strong);
            color: var(--text);
            background: var(--surface-raised);
        }

        .button.primary:hover {
            color: var(--background);
            background: var(--accent-strong);
        }

        .readiness-note {
            margin-top: 1.5rem;
            padding-left: 1rem;
            border-left: 0.2rem solid var(--warm);
            color: var(--muted);
        }

        .section-intro {
            max-width: 48rem;
            color: var(--muted);
        }

        .lifecycle {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.65rem;
            margin: 1.5rem 0 0;
            padding: 0;
            list-style: none;
            counter-reset: lifecycle;
        }

        .lifecycle li {
            min-width: 0;
            padding: 0.9rem;
            border: 1px solid var(--border);
            border-radius: 0.65rem;
            background: rgba(14, 27, 45, 0.78);
            counter-increment: lifecycle;
        }

        .lifecycle li::before {
            display: block;
            margin-bottom: 0.35rem;
            color: var(--warm);
            content: counter(lifecycle, decimal-leading-zero);
            font-family: "SFMono-Regular", Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .folder-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.9rem;
            margin-top: 1.5rem;
        }

        .card {
            min-width: 0;
            padding: 1.1rem;
            border: 1px solid var(--border);
            border-radius: 0.8rem;
            background: var(--surface);
        }

        .card p {
            margin-bottom: 0;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .boundaries {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .boundary {
            padding: 1.25rem;
            border: 1px solid var(--border);
            border-top: 0.25rem solid var(--accent);
            border-radius: 0.8rem;
            background: var(--surface-raised);
        }

        .boundary.generated {
            border-top-color: var(--warm);
        }

        .boundary p:last-child {
            margin-bottom: 0;
            color: var(--muted);
        }

        .next-steps {
            display: grid;
            gap: 0.9rem;
            margin: 1.5rem 0 0;
            padding: 0;
            list-style: none;
            counter-reset: steps;
        }

        .next-steps li {
            display: grid;
            grid-template-columns: 2.4rem 1fr;
            gap: 0.9rem;
            align-items: start;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            counter-increment: steps;
        }

        .next-steps li::before {
            display: grid;
            width: 2.2rem;
            height: 2.2rem;
            place-items: center;
            border-radius: 999px;
            color: var(--background);
            background: var(--warm);
            content: counter(steps);
            font-weight: 900;
        }

        .next-steps p {
            margin-bottom: 0;
            color: var(--muted);
        }

        .command {
            display: block;
            width: fit-content;
            max-width: 100%;
            margin-top: 0.45rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid var(--border);
            border-radius: 0.45rem;
            background: #07111f;
        }

        .opia-note {
            padding: 1.25rem;
            border: 1px dashed var(--border);
            border-radius: 0.8rem;
            background: rgba(14, 27, 45, 0.58);
        }

        .opia-note p {
            max-width: 52rem;
            margin-bottom: 0;
            color: var(--muted);
        }

        .site-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 1rem;
            padding-block: 1.5rem 2rem;
            border-top: 1px solid var(--border);
            color: var(--muted);
            font-size: 0.9rem;
        }

        .site-footer p {
            margin: 0;
        }

        @media (prefers-reduced-motion: reduce) {
            html {
                scroll-behavior: auto;
            }
        }

        @media (max-width: 48rem) {
            main {
                padding-top: 1.5rem;
            }

            .lifecycle,
            .folder-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .boundaries {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 34rem) {
            .site-header {
                align-items: flex-start;
                flex-direction: column;
            }

            .hero {
                padding-top: 1.5rem;
            }

            .lifecycle,
            .folder-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <header class="site-header">
        <a class="brand" href="/" aria-label="Meulah home">
            <span class="brand-mark" aria-hidden="true">M</span>
            <span>Meulah</span>
        </a>
        <span class="status"><?= $escape($applicationName) ?> application running</span>
    </header>

    <main id="main-content">
        <section class="hero" aria-labelledby="welcome-title">
            <p class="eyebrow">Framework 0.2 starter</p>
            <h1 id="welcome-title">Meulah is running.</h1>
            <p class="lead">
                Your application booted, matched the named <code>home</code> route,
                resolved its controller, rendered this plain PHP view, and returned a response.
            </p>
            <div class="actions" aria-label="Project repositories">
                <a class="button primary" href="https://github.com/Meulah/framework">Explore the framework</a>
                <a class="button" href="https://github.com/Meulah/meulah">View the starter</a>
            </div>
            <p class="readiness-note">
                This is a clean starting point, not a claim that an application is production-ready.
                Review deployment, security, observability, and application-specific requirements before launch.
            </p>
        </section>

        <section aria-labelledby="lifecycle-title">
            <p class="eyebrow">One explicit path</p>
            <h2 id="lifecycle-title">Request lifecycle</h2>
            <p class="section-intro">
                Meulah keeps startup visible. A request moves through these files without hidden providers or route discovery.
            </p>
            <ol class="lifecycle">
                <li><code>public/index.php</code></li>
                <li><code>start/app.php</code></li>
                <li><code>app/bindings.php</code></li>
                <li><code>start/middleware.php</code></li>
                <li><code>start/routes.php</code></li>
                <li><code>routes/web.php</code></li>
                <li><code>HomeController</code></li>
                <li><code>views/home.php</code></li>
                <li><code>Response</code></li>
            </ol>
        </section>

        <section aria-labelledby="folders-title">
            <p class="eyebrow">Project map</p>
            <h2 id="folders-title">Know where things belong</h2>
            <p class="section-intro">
                Each top-level folder has a narrow purpose, so a new application stays easy to navigate.
            </p>
            <div class="folder-grid">
                <article class="card">
                    <h3><code>app/</code></h3>
                    <p>Controllers, middleware, services, and application dependency bindings.</p>
                </article>
                <article class="card">
                    <h3><code>start/</code></h3>
                    <p>The composition root, global middleware order, and explicit route loading.</p>
                </article>
                <article class="card">
                    <h3><code>settings/</code></h3>
                    <p>Validated application, HTTP, database, and file-path settings.</p>
                </article>
                <article class="card">
                    <h3><code>routes/</code></h3>
                    <p>Named HTTP routes that connect requests to application handlers.</p>
                </article>
                <article class="card">
                    <h3><code>views/</code></h3>
                    <p>Plain PHP presentation files. Dynamic values must be escaped for their output context.</p>
                </article>
                <article class="card">
                    <h3><code>database/</code></h3>
                    <p>Ordered migration files. The default SQLite database itself lives under <code>data/</code>.</p>
                </article>
                <article class="card">
                    <h3><code>data/uploads/</code></h3>
                    <p>Persistent application-owned uploads, kept outside the public document root.</p>
                </article>
                <article class="card">
                    <h3><code>runtime/</code></h3>
                    <p>Generated logs, caches, sessions, and other regenerable runtime output.</p>
                </article>
                <article class="card">
                    <h3><code>public/</code></h3>
                    <p>The web server document root and the only intended public entry point.</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="boundaries-title">
            <p class="eyebrow">Keep the boundary clear</p>
            <h2 id="boundaries-title">Persistent data is not runtime data</h2>
            <div class="boundaries">
                <article class="boundary">
                    <h3><code>data/uploads/</code></h3>
                    <p><strong>Persistent application-owned files.</strong></p>
                    <p>Back these up deliberately and never assume they can be regenerated.</p>
                </article>
                <article class="boundary generated">
                    <h3><code>runtime/</code></h3>
                    <p><strong>Generated or regenerable runtime files.</strong></p>
                    <p>Its contents may be cleared when the application is stopped and deployment procedures allow it.</p>
                </article>
            </div>
        </section>

        <section aria-labelledby="steps-title">
            <p class="eyebrow">Continue with confidence</p>
            <h2 id="steps-title">Three verified next steps</h2>
            <ol class="next-steps">
                <li>
                    <div>
                        <h3>Follow this page through the code</h3>
                        <p>Open <code>routes/web.php</code>, then <code>HomeController</code>, then <code>views/home.php</code>.</p>
                    </div>
                </li>
                <li>
                    <div>
                        <h3>See the available CLI commands</h3>
                        <code class="command">php meulah --help</code>
                    </div>
                </li>
                <li>
                    <div>
                        <h3>Check the first-run database</h3>
                        <code class="command">php meulah migrate:status</code>
                    </div>
                </li>
            </ol>
        </section>

        <section class="opia-note" aria-labelledby="opia-title">
            <h2 id="opia-title">Templates today</h2>
            <p>
                This starter uses plain PHP views. Opia is currently a proposed template-language design;
                it is not implemented or enabled in Framework 0.2, and this starter contains no Opia templates.
            </p>
        </section>
    </main>

    <footer class="site-footer">
        <p>Built with Meulah Framework 0.2.</p>
        <p>
            <a href="https://github.com/Meulah/framework">Framework repository</a>
            ?
            <a href="https://github.com/Meulah/meulah">Starter repository</a>
        </p>
    </footer>
</body>
</html>
