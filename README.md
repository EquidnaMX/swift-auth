# SwiftAuth

**Bottled authentication for Laravel projects.**

SwiftAuth is a production-ready authentication package for Laravel that provides a robust, secure, and flexible identity management system. It supports traditional session-based auth, multi-factor authentication (OTP & WebAuthn/Passkeys), role-based access control, and comprehensive session management.

This package is designed to be a drop-in solution for Laravel applications requiring enterprise-grade authentication features without the boilerplate.

---

## Documentation Index

-   [Deployment Instructions](doc/deployment-instructions.md)
-   [Securing Routes](doc/securing-routes.md) **← Session & API Token Authentication**
-   [API Documentation](doc/api-documentation.md) (Public Endpoints)
-   [Routes Documentation](doc/routes-documentation.md)
-   [Artisan Commands](doc/artisan-commands.md)
-   [Tests Documentation](doc/tests-documentation.md)
-   [Architecture Diagrams](doc/architecture-diagrams.md)
-   [Monitoring](doc/monitoring.md)
-   [Business Logic & Core Processes](doc/business-logic-and-core-processes.md)
-   [Open Questions & Assumptions](doc/open-questions-and-assumptions.md)

> This documentation and the codebase follow the project’s **Coding Standards Guide** and **PHPDoc Style Guide**.

## Tech Stack & Requirements

-   **Type:** Laravel Package
-   **PHP:** 8.2+
-   **Laravel:** 11.x / 12.x
-   **Key Dependencies:**
    -   `equidna/bird-flock` (Notification Bus)
    -   `laragear/webauthn` (Passkey Support)
    -   `inertiajs/inertia-laravel` (Frontend Interop)

## Quick Start

1.  **Install the package:**

    ```bash
    composer require equidna/swift-auth
    ```

2.  **Publish assets and configuration:**

    ```bash
    php artisan swift-auth:install
    ```

    This will publish the config file (`config/swift-auth.php`), migrations, and frontend assets.

3.  **Run migrations:**

    ```bash
    php artisan migrate
    ```

4.  **Create an initial admin user:**

    ```bash
    php artisan swift-auth:create-admin-user
    ```

5.  **Serve and visit:**

    Start your server:

    ```bash
    php artisan serve
    ```

    Visit `/swift-auth/login` (or your configured route prefix) to see the login page.

## Localization

SwiftAuth includes full localization support for **English** and **Spanish**. Users can dynamically switch languages through a UI component, and the package automatically persists their preference in the session.

### Supported Languages

-   **English (en)** - Default
-   **Spanish (es)**

### Features

-   Dynamic language switching via `LanguageSwitcher` component
-   Session-based locale persistence
-   All UI elements, emails, and notifications are fully translated
-   Translation files organized by module (auth, email, session, user, role)

### Usage

**In PHP/Blade:**

```php
{{ __('swift-auth::auth.login_title') }}
@lang('swift-auth::email.verification_button')
```

**In JavaScript/TypeScript:**

```typescript
import { __ } from "../../../lang/translations";
<h1>{__("auth.login_title")}</h1>;
```

For comprehensive localization documentation, including how to add new languages and customize translations, see [Localization Guide](doc/localization.md).

## Securing Routes

SwiftAuth provides two authentication systems:

**Session Authentication (Web):**

```php
Route::middleware('SwiftAuth.RequireAuthentication')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// With role-based access
Route::middleware([
    'SwiftAuth.RequireAuthentication',
    'SwiftAuth.CanPerformAction:admin-panel',
])->group(function () {
    Route::get('/admin', [AdminController::class, 'index']);
});
```

**API Token Authentication:**

```php
Route::middleware('SwiftAuth.AuthenticateWithToken')->group(function () {
    Route::get('/api/posts', [PostController::class, 'index']);
});

// With ability-based access
Route::middleware([
    'SwiftAuth.AuthenticateWithToken',
    'SwiftAuth.CheckTokenAbilities:posts:create',
])->group(function () {
    Route::post('/api/posts', [PostController::class, 'store']);
});
```

For complete examples, testing strategies, and best practices, see **[Securing Routes Guide](doc/securing-routes.md)**.
