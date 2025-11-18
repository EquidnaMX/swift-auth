# Swift Auth

Swift Auth is a compact, production-oriented authentication and authorization package suitable for Laravel-style applications. This README focuses on installation, configuration, and the recent security and workflow changes.

## Quick Summary

-   Namespace: `Equidna\SwiftAuth`
-   Admin creation: `php artisan swift-auth:create-admin` (name/email required via args or env; password is always randomly generated and never printed)
-   Password resets: configurable TTL and per-email rate-limiting

## Quick Install

1. Install via Composer (use your package name):

```powershell
composer require equidna/swift-auth
```

1. Publish package assets and configuration:

```powershell
php artisan swift-auth:install
```

1. Run migrations:

```powershell
php artisan migrate
```

1. Create the initial admin (interactive or non-interactive using env vars):

```powershell
# Interactive (prompts for name/email)
php artisan swift-auth:create-admin "Admin Name" "admin@example.test"

# Non-interactive when env vars are set
$env:SWIFT_ADMIN_NAME='CI Admin'; $env:SWIFT_ADMIN_EMAIL='ci-admin@example.test'; php artisan swift-auth:create-admin
```

After code or namespace changes, regenerate autoload:

```powershell
composer dump-autoload -o
```

## Admin creation behavior (important)

-   The `create-admin` command requires `name` and `email` either as command arguments or via the environment variables `SWIFT_ADMIN_NAME` and `SWIFT_ADMIN_EMAIL`.
-   The command always generates a cryptographically-random password and stores the hashed value. The password is never printed or written to logs.
-   The created admin's `email_verified_at` is left `null` to force a password reset flow for the new user.

This removes any plaintext-secret workflow and reduces accidental credential exposure.

## Password reset hardening

-   `password_reset_ttl` (seconds) — TTL for reset tokens. Default: `900` (15 minutes).
-   `password_reset_rate_limit` — Rate-limiter settings applied per email (hashed key) to reduce enumeration and abuse. Example:

```php
'password_reset_rate_limit' => [
    'attempts' => 5,
    'decay_seconds' => 60,
],
```

-   Reset emails are queued by default; run a queue worker in production to avoid blocking requests.

## Configuration

Publishable file: `config/swift-auth.php`.
Key entries to review:

-   `password_reset_ttl` (int seconds)
-   `password_reset_rate_limit` (array)

Note: the `admin_user` config and any stored admin passwords have been removed for security — use the `create-admin` command instead.

## Mail / Queue recommendation

Use a queue driver (e.g. `database`) and run a worker:

```powershell
QUEUE_CONNECTION=database
php artisan queue:work
```

## Namespace & upgrade notes

The package uses the `Equidna\SwiftAuth` namespace. If you upgraded from an older package using `Teleurban\SwiftAuth`, update any published files and run:

```powershell
composer dump-autoload -o
php artisan vendor:publish --provider="Equidna\SwiftAuth\Providers\SwiftAuthServiceProvider" --tag=swift-auth:config
```

## Commands

-   `php artisan swift-auth:install` — publishes config, views, and migrations
-   `php artisan swift-auth:create-admin [name] [email]` — creates an admin user (password always random)

## Environment variables

Set these in your application's `.env` file:

-   `SWIFT_AUTH_FRONTEND` — `blade`, `typescript`, or `javascript` (installer default)
-   `SWIFT_AUTH_SUCCESS_URL` — redirect URL after successful login
-   `SWIFT_ADMIN_NAME` — initial admin full name (optional, used by `create-admin`)
-   `SWIFT_ADMIN_EMAIL` — initial admin email (optional, used by `create-admin`)

## Security recommendations

Swift Auth ships with sensible defaults, but production deployments should harden cookies and sessions. Review `SECURITY.md` for:

-   Recommended `SESSION_*` and `SWIFT_AUTH_SUCCESS_URL` values (secure, HTTP-only, same-site strict, HTTPS redirects).
-   A checklist of runtime package dependencies (`laravel/framework`, `equidna/laravel-toolkit`, `inertiajs/inertia-laravel`).
-   Additional HTTP header suggestions (HSTS, CSP, X-Frame-Options) for reverse proxies.

## Testing & Linting

-   PHPCS is configured (PSR-12). Blade templates are excluded from PSR-12 checks via `phpcs.xml`.
-   Unit tests belong under `tests/Unit` and must be isolated (mock external I/O). See repository `phpcs.xml` and `TestingScope` guidance.

## Contributing

1. Fork and branch.
2. Respect PSR-12 and the repository `phpcs.xml` rules.
3. Add unit tests under `tests/Unit` for logic changes.
4. Open a PR with upgrade notes if behavior or namespaces changed.

## License

MIT — see `LICENSE`.
