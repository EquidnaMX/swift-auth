# Artisan Commands

 SwiftAuth includes several commands for installation, maintenance, and administration.

## Installation & Setup

### `swift-auth:install`
Publishes configuration, migrations, and assets.

```bash
php artisan swift-auth:install
```

### `swift-auth:create-admin-user`
Interactively creates a user with the `Admin` role.

```bash
php artisan swift-auth:create-admin-user
```

## Maintenance & Cleanup

### `swift-auth:purge-stale-sessions`
Removes database session records that have exceeded their lifetime.
*Scheduled automatically if `session_cleanup.enabled` is true.*

```bash
php artisan swift-auth:purge-stale-sessions
```

### `swift-auth:purge-expired-tokens`
Removes expired password reset tokens and email verification tokens.
*Scheduled automatically (hourly).*

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
