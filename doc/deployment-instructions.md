# Deployment Instructions

This guide covers the installation, configuration, and deployment requirements for the **SwiftAuth** package.

## System Requirements

-   **PHP:** 8.2 or higher.
-   **Extensions:** `json`, `pdo`, `openssl`, `mbstring`.
-   **Laravel Framework:** 11.x or 12.x.
-   **Database:** MySQL 8.0+, PostgreSQL 13+, or SQLite.
-   **Dependencies:**
    -   `equidna/bird-flock` (for email/notifications).
    -   `laragear/webauthn` (for Passkeys).

## Installation

### 1. Require via Composer

Can be installed via Composer (assuming configured repositories):

```bash
composer require equidna/swift-auth
```

### 2. Install Assets & Config

Run the guided installer command:

```bash
php artisan swift-auth:install
```

Or publish manually:

```bash
# Config
php artisan vendor:publish --tag=swift-auth:config

# Migrations
php artisan vendor:publish --tag=swift-auth:migrations

# Assets (Views/Lang)
php artisan vendor:publish --tag=swift-auth:views
php artisan vendor:publish --tag=swift-auth:lang
```

### 3. Database Setup

Run the migrations to create User, Role, Session, UserToken, and related tables:

```bash
php artisan migrate
```

> **Note:** SwiftAuth uses its own tables (`swift-auth_Users`, etc.) by default, configured via `table_prefix`.

## Configuration

The main configuration file is `config/swift-auth.php`.

### Key Environment Variables

| Variable                        | Default       | Description                                              |
| :------------------------------ | :------------ | :------------------------------------------------------- |
| `SWIFT_AUTH_FRONTEND`           | `typescript`  | Frontend stack (`blade`, `typescript`, or `javascript`). |
| `SWIFT_AUTH_ALLOW_REGISTRATION` | `false`       | Enable public user registration.                         |
| `SWIFT_AUTH_ROUTE_PREFIX`       | `swift-auth`  | URL prefix for all package routes.                       |
| `SWIFT_AUTH_TABLE_PREFIX`       | `swift-auth_` | Database table prefix.                                   |
| `SWIFT_AUTH_MFA_ENABLED`        | `false`       | Enable Multi-Factor Authentication.                      |
| `SWIFT_AUTH_LOCKOUT_ENABLED`    | `true`        | Enable account lockout protection.                       |

## Frontend Setup

SwiftAuth supports three frontend stacks: **Blade**, **React + TypeScript**, and **React + JavaScript**.

### Blade (Default for Simple Projects)

When using Blade views, no additional setup is required. Views are served directly from the package:

```bash
php artisan swift-auth:install
# Select: Blade
```

Set in `.env`:

```
SWIFT_AUTH_FRONTEND=blade
```

### React + TypeScript (Recommended)

For modern React applications with TypeScript and Inertia.js:

#### 1. Install and Publish

```bash
php artisan swift-auth:install
# Select: React + TypeScript

npm install
```

#### 2. Configure Vite

Update your `vite.config.js` to include SwiftAuth components:

```javascript
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/js/app.jsx",
                "resources/ts/swift-auth/pages/**/*.tsx",
                "resources/ts/swift-auth/components/**/*.tsx",
            ],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            "@": "/resources/js",
            "@swift-auth": "/resources/ts/swift-auth",
        },
    },
});
```

#### 3. Configure Inertia

Ensure your Inertia middleware resolves components correctly. Update `app/Http/Middleware/HandleInertiaRequests.php`:

```php
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // Your shared data
        ]);
    }
}
```

Create/update your app layout blade file (e.g., `resources/views/app.blade.php`):

```blade
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    @vite(['resources/js/app.jsx', 'resources/css/app.css'])
    @inertiaHead
  </head>
  <body>
    @inertia
  </body>
</html>
```

Configure Inertia root view in `config/inertia.php`:

```php
return [
    'ssr' => [
        'enabled' => false,
    ],
];
```

Update your main JavaScript entry point (`resources/js/app.jsx`) to resolve SwiftAuth components:

```javascript
import { createInertiaApp } from "@inertiajs/react";
import { createRoot } from "react-dom/client";

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob(
            ["./Pages/**/*.jsx", "../ts/swift-auth/pages/**/*.tsx"],
            { eager: true }
        );

        // Support SwiftAuth/ namespace with PascalCase directory structure
        // e.g., SwiftAuth/User/Index -> ../ts/swift-auth/pages/User/Index.tsx
        let pagePath = name.startsWith("SwiftAuth/")
            ? `../ts/swift-auth/pages/${name.replace("SwiftAuth/", "")}.tsx`
            : `./Pages/${name}.jsx`;

        return pages[pagePath];
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
```

#### 4. Component Namespacing in Controllers

SwiftAuth controllers use the `SwiftAuth/` namespace when rendering Inertia components via the `SelectiveRender` trait:

```php
// Inside a SwiftAuth controller
use Equidna\SwiftAuth\Support\Traits\SelectiveRender;

public function showUserIndex(Request $request): View|Response
{
    return $this->render(
        'swift-auth::user.index',              // Blade view
        'SwiftAuth/User/Index',                // Inertia component (PascalCase)
    );
}
```

The trait's `render()` method:

-   Renders Blade views for `blade` frontend
-   Renders namespaced Inertia components (PascalCase, e.g., `SwiftAuth/User/Index`) for `typescript`/`javascript` frontends
-   Automatically injects flash messages into view data
-   Automatically injects flash messages (success, error, status) into view data

#### 5. Build Assets

```bash
npm run dev    # Development with hot reload
npm run build  # Production build
```

Set in `.env`:

```
SWIFT_AUTH_FRONTEND=typescript
```

### React + JavaScript

Similar to TypeScript setup, but use JavaScript paths:

```javascript
// vite.config.js
input: [
    'resources/js/app.jsx',
    'resources/js/swift-auth/pages/**/*.jsx',
    'resources/js/swift-auth/components/**/*.jsx',
],

// app.jsx resolver
const pages = import.meta.glob([
    './Pages/**/*.jsx',
    './swift-auth/pages/**/*.jsx',
], { eager: true })

let pagePath = name.startsWith('SwiftAuth/')
    ? `./swift-auth/pages/${name.replace('SwiftAuth/', '')}.jsx`
    : `./Pages/${name}.jsx`
```

Set in `.env`:

```
SWIFT_AUTH_FRONTEND=javascript
```

> **Note:** SwiftAuth uses the `SwiftAuth/` namespace for Inertia components (e.g., `SwiftAuth/Login`) to avoid conflicts with your application's components.

### Session Cleanup

SwiftAuth includes a session garbage collector. Ensure your scheduler is running:

```bash
# In production crontab/scheduler
php artisan schedule:run
```

This ensures `swift-auth:purge-stale-sessions` and `swift-auth:purge-expired-tokens` run as configured (hourly/daily).

## Production Optimization

When deploying to production:

1.  **Build Frontend Assets:**
    ```bash
    npm run build
    ```
2.  **Cache Configuration:**
    ```bash
    php artisan config:cache
    ```
3.  **Route Caching:**
    ```bash
    php artisan route:cache
    ```
    _SwiftAuth routes are compatible with route caching._
4.  **Optimize Autoloader:**
    ```bash
    composer install --optimize-autoloader --no-dev
    ```
