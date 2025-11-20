# Upgrading Notes

This file lists important upgrade tasks and compatibility notes for consumers of `equidna/swift-auth`.

## 1.0.1 — Important changes

-   Replaced `equidna/toolkit` with `equidna/laravel-toolkit`. Ensure your application requires a compatible version of `equidna/laravel-toolkit`.
-   The package `ServiceProvider` remains `Equidna\SwiftAuth\Providers\SwiftAuthServiceProvider` and can be published via:

```powershell
php artisan vendor:publish --provider="Equidna\SwiftAuth\Providers\SwiftAuthServiceProvider" --tag=swift-auth:config
```

## Password configuration

-   Password strength and hashing algorithm are now configurable. See `config/swift-auth.php` for options:

    -   `password_min_length` — minimum password length used by package validators (default: `8`).
    -   `password_min_length` — convenience min length used on login forms (default: `8`).
    -   `hash_driver` — optional Laravel hash driver name (e.g. `argon`, `bcrypt`). If `null`, the application default Hash driver is used.

Update your published config as needed before upgrading in production.

## Database table names

-   The package uses a configurable `table_prefix` (`swift-auth_` by default) and capitalized table names (e.g. `Users`, `Roles`). If you previously published and modified migrations, ensure you re-publish and re-run migrations where appropriate.

## Recommendations

-   Add unit tests and CI before upgrading production systems.
-   Configure a queue worker and secure session settings as described in `SECURITY.md`.
