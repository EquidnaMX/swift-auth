# Copilot Instructions — SwiftAuth (Laravel Package)

These instructions help AI coding agents work productively in this repository. Focus on practical tasks, minimal complexity, and Laravel conventions.

## Architecture Overview

-   **Package type:** Composer library providing session-based authentication for Laravel 11/12.
-   **Core Components:**
    -   `src/Services/SwiftSessionAuth.php`: Session auth orchestrator (login/logout, session enforcement, MFA initiation).
    -   `src/Http/Controllers/AuthController.php`: Login/logout flows, MFA entry, rate limiting (now using `ChecksRateLimits`).
    -   `src/Http/Controllers/PasswordController.php`: Password reset request + update, rate limiting.
    -   `src/Models/{User, Role, UserSession, RememberToken, PasswordResetToken}.php`: Eloquent models with table prefix from `config('swift-auth.table_prefix')`.
    -   `src/Facades/SwiftAuth.php`: Facade for `SwiftSessionAuth` service; check, login, logout, sessions, RBAC.
    -   `src/Traits/ChecksRateLimits.php`: Shared rate limiting helpers used by controllers.
    -   `src/routes/*.php`: Route groups, prefix from `config('swift-auth.route_prefix')`.
    -   `src/config/swift-auth.php`: All package configuration.
-   **Data flow:** Controllers → Facade (`SwiftAuth`) → Service (`SwiftSessionAuth`) → Models (Eloquent). Events, rate limiters, and session/cookie helpers are used along the way.

## Developer Workflows

-   **Install dependencies:**
    ```powershell
    composer install
    ```
-   **Run unit tests only (recommended for agents):**
    ```powershell
    .\vendor\bin\phpunit --testsuite Unit --testdox
    ```
-   **Feature tests require a full Laravel app context:** They may fail in isolation. Agents should avoid changing them unless coordinating with app bootstrap owners.
-   **Static analysis & style:** PHPStan + PHPCS are configured.
    -   `phpstan.neon` (level: strict, Larastan enabled)
    -   `ruleset.xml` (PSR-12, line length 120, excludes tests/vendor)

## Conventions & Patterns

-   **Table/Route Prefix:** Always derive from config: `config('swift-auth.table_prefix')`, `config('swift-auth.route_prefix')`.
-   **Rate Limiting:** Use `ChecksRateLimits` trait instead of duplicating `RateLimiter::tooManyAttempts/hit/clear` logic.
-   **RBAC:**
    -   `User::roles()` is `BelongsToMany` to `Role` via `{prefix}UsersRoles`.
    -   Use `User::hasRole()` / `->availableActions()`; facade exposes `SwiftAuth::hasRole()` and `SwiftAuth::canPerformAction()`.
-   **Responses:** Prefer `Equidna\Toolkit\Helpers\ResponseHelper::success()` for consistent JSON/redirect behavior.
-   **MFA:** Config-driven; `SwiftAuth::startMfaChallenge()` with `driver` (`otp|webauthn`).
-   **Password Reset:** Tokens are SHA-256 hashes; compare using `hash_equals(hash('sha256', $raw), $stored)`.
-   **No ConfigHelper:** Use direct `config()` calls—keep the package simple.

## Integration Points

-   **Service Provider:** `SwiftAuthServiceProvider` auto-registers facade binding as `swift-auth`.
-   **Middleware:** `RequireAuthentication`, `CanPerformAction`, and `SwiftAuth.SecurityHeaders` in routes.
-   **Frontend:** Blade or Inertia; controllers return Blade views or JSON based on request context.

## Safe Change Guidelines (Agents)

-   **Scope:** Prefer changes in `src/**`. Avoid modifying `tests/Feature/**` unless the Laravel app bootstrap is prepared.
-   **Unit tests only:** Follow `tests/Unit/**` and repository TestingScope instructions (mock external deps, no I/O).
-   **Keep simplicity:** Reduce duplication; do not introduce new abstractions unless necessary.
-   **Respect public APIs:** Do not rename facade methods or change signatures. Add minimal helpers if needed.
-   **Use config:** Derive all behavior from `config('swift-auth.*')`; avoid hardcoding.

## Useful Examples

-   **BelongsToMany with table prefix:**
    ```php
    $prefix = (string) config('swift-auth.table_prefix', '');
    return $this->belongsToMany(Role::class, $prefix.'UsersRoles', 'id_user', 'id_role');
    ```
-   **Rate limit check in controllers:**
    ```php
    $this->checkRateLimit($key, $attempts, 'Too many attempts.');
    $this->hitRateLimit($key, $decay);
    $this->clearRateLimit($key);
    ```
-   **Password reset token comparison:**
    ```php
    $expected = hash('sha256', $data['token']);
    if (!hash_equals($reset->token, $expected)) { /* error */ }
    ```

## Where to Look First

-   `src/Http/Controllers/AuthController.php` — end-to-end login flow.
-   `src/Services/SwiftSessionAuth.php` — core session auth logic.
-   `src/config/swift-auth.php` — behavior toggles and limits.
-   `src/routes/swift-auth.php` — public and admin route wiring.

---

Questions or unclear areas? Share specifics (file/line) and intended behavior; we’ll refine these instructions accordingly.
