# Deployment Instructions

This guide covers the installation, configuration, and deployment requirements for the **SwiftAuth** package.

## System Requirements

- **PHP:** 8.2 or higher.
- **Extensions:** `json`, `pdo`, `openssl`, `mbstring`.
- **Laravel Framework:** 11.x or 12.x.
- **Database:** MySQL 8.0+, PostgreSQL 13+, or SQLite.
- **Dependencies:**
  - `equidna/bird-flock` (for email/notifications).
  - `laragear/webauthn` (for Passkeys).

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

Run the migrations to create User, Role, Session, and WebAuthn tables:

```bash
php artisan migrate
```

> **Note:** SwiftAuth uses its own tables (`swift-auth_Users`, etc.) by default, configured via `table_prefix`.

## Configuration

The main configuration file is `config/swift-auth.php`.

### Key Environment Variables

| Variable | Default | Description |
| :--- | :--- | :--- |
| `SWIFT_AUTH_FRONTEND` | `typescript` | Frontend stack (`blade` or `typescript`/Inertia). |
| `SWIFT_AUTH_ALLOW_REGISTRATION` | `false` | Enable public user registration. |
| `SWIFT_AUTH_ROUTE_PREFIX` | `swift-auth` | URL prefix for all package routes. |
| `SWIFT_AUTH_TABLE_PREFIX` | `swift-auth_` | Database table prefix. |
| `SWIFT_AUTH_MFA_ENABLED` | `false` | Enable Multi-Factor Authentication. |
| `SWIFT_AUTH_LOCKOUT_ENABLED` | `true` | Enable account lockout protection. |

### Session Cleanup

SwiftAuth includes a session garbage collector. Ensure your scheduler is running:

```bash
# In production crontab/scheduler
php artisan schedule:run
```

This ensures `swift-auth:purge-stale-sessions` and `swift-auth:purge-expired-tokens` run as configured (hourly/daily).

## Production Optimization

When deploying to production:

1.  **Cache Configuration:**
    ```bash
    php artisan config:cache
    ```
2.  **Route Caching:**
    ```bash
    php artisan route:cache
    ```
    *SwiftAuth routes are compatible with route caching.*
3.  **Optimize Autoloader:**
    ```bash
    composer install --optimize-autoloader --no-dev
    ```
