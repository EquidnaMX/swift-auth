# Artisan Commands

SwiftAuth includes several commands for installation, maintenance, and administration.

## Installation & Setup

### `swift-auth:install`

Publishes configuration, migrations, and assets.

```bash
php artisan swift-auth:install
```

### `swift-auth:create-admin-user`

Creates a user with the `root` admin role. Prompts securely for password.

```bash
php artisan swift-auth:create-admin "Admin Name" admin@example.com
```

**Password Handling:**

-   Password is always prompted securely (never passed as argument)
-   Leave empty to auto-generate a random password
-   Generated passwords are displayed once and must be saved

## Maintenance & Cleanup

### `swift-auth:purge-stale-sessions`

Removes database session records that have exceeded their lifetime.
_Scheduled automatically if `session_cleanup.enabled` is true._

```bash
php artisan swift-auth:purge-stale-sessions
```

### `swift-auth:purge-expired-tokens`

Removes expired password reset tokens, email verification tokens, and user API tokens.
_Scheduled automatically (hourly)._

```bash
php artisan swift-auth:purge-expired-tokens
```

## Administration (Manual)

### `swift-auth:list-sessions`

Lists active sessions for a specific user ID.

```bash
php artisan swift-auth:list-sessions --user=1
```

### `swift-auth:revoke-user-sessions`

Invalidates all sessions for a user.

```bash
php artisan swift-auth:revoke-user-sessions --user=1
```

### `swift-auth:unlock-user`

Manually unlocks a user account that was locked due to too many failed login attempts.

```bash
php artisan swift-auth:unlock-user --email=user@example.com
```

### `swift-auth:preview-email-templates`

Renders email notifications to HTML files for design verification.

```bash
php artisan swift-auth:preview-email-templates
```
