# SwiftAuth

**Bottled authentication for Laravel projects.**

SwiftAuth is a lightweight, production-ready authentication package for Laravel 11+ that provides session management, role-based access control (RBAC), email verification, password reset, MFA support, and account lockout—without the complexity of full authentication scaffolding frameworks.

## Why SwiftAuth?

-   **Simple Integration**: Drop into any Laravel project with minimal configuration
-   **Session-Based Auth**: Traditional, stateful authentication with configurable timeouts
-   **RBAC Built-In**: Role and permission management out of the box
-   **Security First**: Rate limiting, account lockout, timing-safe comparisons, secure remember-me tokens
-   **Flexible Frontend**: Works with Blade, Inertia.js (Vue/React), or custom JSON APIs
-   **Zero Bloat**: Only what you need—no opinionated UI, no unnecessary abstractions

## Requirements

-   PHP 8.2+
-   Laravel 11.21+ or 12.0+
-   Inertia.js (optional, for SPA frontends)

## Installation

### 1. Install via Composer

```bash
composer require equidna/swift-auth
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=swift-auth-config
```

This creates `config/swift-auth.php`.

### 3. Run Migrations

```bash
php artisan migrate
```

Creates tables:

-   `swift-auth_Users` (or custom prefix)
-   `swift-auth_Roles`
-   `swift-auth_UsersRoles`
-   `swift-auth_Sessions`
-   `swift-auth_RememberTokens`
-   `swift-auth_PasswordResetTokens`

### 4. (Optional) Publish Views

If using Blade templates:

```bash
php artisan vendor:publish --tag=swift-auth-views
```

## Configuration

All configuration is in `config/swift-auth.php`. Key settings:

### Frontend Stack

```php
'frontend' => env('SWIFT_AUTH_FRONTEND', 'typescript'), // typescript | javascript | blade
```

### Session Management

```php
'session_lifetimes' => [
    'idle_timeout_seconds' => 1800,     // 30 minutes
    'absolute_timeout_seconds' => 86400, // 24 hours
],

'session_limits' => [
    'max_sessions' => 5,          // null = unlimited
    'eviction' => 'oldest',       // oldest | newest
],
```

### Rate Limiting

```php
'login_rate_limits' => [
    'email' => ['attempts' => 5, 'decay_seconds' => 300],
    'ip' => ['attempts' => 20, 'decay_seconds' => 300],
],
```

### Account Lockout

```php
'account_lockout' => [
    'enabled' => true,
    'max_attempts' => 5,
    'lockout_duration' => 900, // 15 minutes
    'reset_after' => 3600,     // Reset counter after 1 hour
],
```

### Password Requirements

```php
'password_min_length' => 8,
'password_requirements' => [
    'require_letters' => false,
    'require_mixed_case' => false,
    'require_numbers' => false,
    'require_symbols' => false,
    'disallow_common_passwords' => false,
],
```

### Remember Me

```php
'remember_me' => [
    'enabled' => true,
    'ttl_seconds' => 1209600, // 14 days
    'rotate_on_use' => true,
    'policy' => 'strict',     // strict | lenient
],
```

### MFA (Multi-Factor Authentication)

```php
'mfa' => [
    'enabled' => false,
    'driver' => 'otp', // otp | webauthn
    'verification_url' => '/mfa/verify',
],
```

### Email Verification

```php
'email_verification' => [
    'required' => false,
    'token_ttl' => 86400, // 24 hours
],
```

### Table & Route Prefix

```php
'table_prefix' => env('SWIFT_AUTH_TABLE_PREFIX', 'swift-auth_'),
'route_prefix' => env('SWIFT_AUTH_ROUTE_PREFIX', 'swift-auth'),
```

## Usage

### Basic Authentication

#### Login

```php
use Equidna\SwiftAuth\Facades\SwiftAuth;

// In your controller
$result = SwiftAuth::login(
    user: $user,
    ipAddress: $request->ip(),
    userAgent: $request->userAgent(),
    deviceName: $request->header('X-Device-Name', ''),
    remember: $request->boolean('remember')
);

// Returns: ['evicted_session_ids' => [...]]
```

#### Check Authentication

```php
if (SwiftAuth::check()) {
    $user = SwiftAuth::user();
    $userId = SwiftAuth::id();
}
```

#### Logout

```php
SwiftAuth::logout();
```

### Middleware

#### Require Authentication

```php
Route::middleware('SwiftAuth.RequireAuthentication')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});
```

#### Role/Permission Check

```php
Route::middleware('SwiftAuth.CanPerformAction:manage-users')->group(function () {
    Route::resource('admin/users', UserController::class);
});
```

### Role-Based Access Control (RBAC)

#### Check User Roles

```php
$user = SwiftAuth::user();

// Check single role
if ($user->hasRole('admin')) {
    // ...
}

// Check multiple roles (any match)
if ($user->hasRole(['admin', 'moderator'])) {
    // ...
}
```

#### Check Permissions

```php
// Via facade
if (SwiftAuth::canPerformAction('edit-posts')) {
    // ...
}

// Via user model
$actions = $user->availableActions(); // ['edit-posts', 'delete-posts', ...]
```

#### Assign Roles

```php
use Equidna\SwiftAuth\Models\Role;

$role = Role::firstOrCreate(['name' => 'editor'], [
    'description' => 'Content editor',
    'actions' => ['edit-posts', 'create-posts'],
]);

$user->roles()->attach($role->id_role);
```

### Session Management

#### List User Sessions

```php
$sessions = SwiftAuth::sessionsForUser($userId);

foreach ($sessions as $session) {
    echo $session->device_name;
    echo $session->ip_address;
    echo $session->last_activity;
}
```

#### Revoke Session

```php
SwiftAuth::revokeSession($userId, $sessionId);
```

#### Enforce Session Limits

```php
// Automatically called during login if max_sessions is set
$evictedIds = SwiftAuth::enforceSessionLimit($user, $currentSessionId);
```

### Password Reset

#### Request Reset

```php
// POST to /swift-auth/password
// Sends email with reset token
```

#### Reset Password

```php
// POST to /swift-auth/password/reset
// With: email, token, password, password_confirmation
```

### Email Verification

#### Send Verification Email

```php
use Equidna\SwiftAuth\Services\NotificationService;

$notificationService->sendEmailVerification($user->email, $token);
```

#### Verify Email

```php
// GET /swift-auth/email/verify/{token}
```

### MFA (Multi-Factor Authentication)

#### Start MFA Challenge

```php
SwiftAuth::startMfaChallenge(
    user: $user,
    driver: 'otp',
    ipAddress: $request->ip(),
    userAgent: $request->userAgent()
);
```

#### Verify OTP

```php
// POST to /swift-auth/mfa/otp/verify
// With: code
```

## Routes

All routes are prefixed with `/swift-auth` (configurable) and named `swift-auth.*`:

### Public Routes

| Method   | URI                 | Name                    | Description                    |
| -------- | ------------------- | ----------------------- | ------------------------------ |
| GET      | `/login`            | `login.form`            | Login form                     |
| POST     | `/login`            | `login`                 | Process login                  |
| GET/POST | `/logout`           | `logout`                | Logout                         |
| GET      | `/users/register`   | `public.register`       | Registration form (if enabled) |
| POST     | `/users`            | `public.register.store` | Process registration           |
| GET      | `/password`         | `password.request.form` | Password reset request form    |
| POST     | `/password`         | `password.request.send` | Send reset email               |
| GET      | `/password/{token}` | `password.reset.form`   | Password reset form            |
| POST     | `/password/reset`   | `password.reset.update` | Process password reset         |

### Authenticated Routes

| Method | URI              | Name               | Description        |
| ------ | ---------------- | ------------------ | ------------------ |
| GET    | `/sessions`      | `sessions.index`   | List user sessions |
| DELETE | `/sessions/{id}` | `sessions.destroy` | Revoke session     |

### Admin Routes (requires `sw-admin` permission)

| Method    | URI                    | Name                     | Description        |
| --------- | ---------------------- | ------------------------ | ------------------ |
| GET       | `/users`               | `users.index`            | List users         |
| POST      | `/users`               | `users.store`            | Create user        |
| GET       | `/users/{id}`          | `users.show`             | View user          |
| PUT/PATCH | `/users/{id}`          | `users.update`           | Update user        |
| DELETE    | `/users/{id}`          | `users.destroy`          | Delete user        |
| GET       | `/roles`               | `roles.index`            | List roles         |
| POST      | `/roles`               | `roles.store`            | Create role        |
| GET       | `/roles/{id}`          | `roles.show`             | View role          |
| PUT/PATCH | `/roles/{id}`          | `roles.update`           | Update role        |
| DELETE    | `/roles/{id}`          | `roles.destroy`          | Delete role        |
| GET       | `/admin/sessions`      | `admin.sessions.index`   | List all sessions  |
| DELETE    | `/admin/sessions/{id}` | `admin.sessions.destroy` | Revoke any session |

## Facade API Reference

```php
use Equidna\SwiftAuth\Facades\SwiftAuth;

// Authentication
SwiftAuth::login(User $user, ...): array
SwiftAuth::logout(): void
SwiftAuth::check(): bool

// User Info
SwiftAuth::user(): ?User
SwiftAuth::userOrFail(): User
SwiftAuth::id(): ?int

// Authorization
SwiftAuth::canPerformAction(string|array $actions): bool
SwiftAuth::hasRole(string|array $roles): bool

// Session Management
SwiftAuth::sessionsForUser(int $userId): Collection
SwiftAuth::revokeSession(int $userId, string $sessionId): void
SwiftAuth::enforceSessionLimit(User $user, string $currentSessionId): array

// MFA
SwiftAuth::startMfaChallenge(User $user, string $driver, ...): void
```

## Models

### User

```php
use Equidna\SwiftAuth\Models\User;

$user = User::find(1);
$user->roles; // BelongsToMany<Role>
$user->hasRole('admin'); // bool
$user->hasRoles(['admin', 'editor']); // bool
$user->availableActions(); // string[]
```

### Role

```php
use Equidna\SwiftAuth\Models\Role;

$role = Role::create([
    'name' => 'editor',
    'description' => 'Content editor',
    'actions' => ['create-posts', 'edit-posts'],
]);

$role->users; // BelongsToMany<User>
```

### UserSession

```php
use Equidna\SwiftAuth\Models\UserSession;

$session = UserSession::where('id_user', $userId)->get();
// Properties: session_id, ip_address, user_agent, device_name, platform, browser, last_activity
```

## Security Features

### Rate Limiting

-   **Per-email**: 5 attempts per 5 minutes (default)
-   **Per-IP**: 20 attempts per 5 minutes (default)
-   **Password reset**: 5 requests per minute per email
-   **Email verification**: 3 resend attempts per 5 minutes

### Account Lockout

-   Locks account after 5 failed login attempts (configurable)
-   15-minute lockout duration (configurable)
-   Counters reset after 1 hour of inactivity

### Timing-Safe Comparisons

-   Password verification uses `hash_equals()` to prevent timing attacks
-   Dummy hash checked when user doesn't exist (constant-time response)

### Remember Me Security

-   SHA-256 hashed tokens
-   Token rotation on use (optional)
-   IP/subnet validation (optional)
-   Device ID validation (optional)
-   Configurable strict/lenient policies

### Session Security

-   Idle timeout (default: 30 minutes)
-   Absolute timeout (default: 24 hours)
-   Concurrent session limits with automatic eviction
-   Session cleanup scheduler

## Security Defaults

-   Cookies: `Secure=true` (prod), `HttpOnly=true`, `SameSite='lax'` (configurable)
-   Remember-me: 14 days TTL, rotation on use (default: true)
-   Rate limiting: conservative defaults; override via `config/swift-auth.php`
-   Token comparisons: `hash_equals()` for constant-time safety

## Recommended DB Indexes

-   `Sessions.session_id` (unique)
-   `Sessions.id_user`
-   `Sessions.last_activity`
-   `RememberTokens.selector` (unique)
-   `Users.email` (unique)
-   `PasswordResetTokens.email`

## Frontend Integration

### Blade (Traditional)

Use published views or create custom forms posting to SwiftAuth routes.

### Inertia.js (SPA)

Set `'frontend' => 'typescript'` or `'javascript'` in config.

```vue
<!-- Login.vue -->
<script setup>
import { useForm } from "@inertiajs/vue3";

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

function submit() {
    form.post("/swift-auth/login");
}
</script>

<template>
    <form @submit.prevent="submit">
        <input v-model="form.email" type="email" />
        <input v-model="form.password" type="password" />
        <input v-model="form.remember" type="checkbox" />
        <button :disabled="form.processing">Login</button>
    </form>
</template>
```

### JSON API

All endpoints support JSON requests/responses when `Accept: application/json` header is present.

```javascript
fetch("/swift-auth/login", {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ email: "user@example.com", password: "secret" }),
})
    .then((res) => res.json())
    .then((data) => console.log(data));
```

## Environment Variables

```bash
# Frontend
SWIFT_AUTH_FRONTEND=typescript

# Registration
SWIFT_AUTH_ALLOW_REGISTRATION=true

# Redirect
SWIFT_AUTH_SUCCESS_URL=/dashboard

# Sessions
SWIFT_AUTH_SESSION_IDLE_TIMEOUT=1800
SWIFT_AUTH_SESSION_ABSOLUTE_TIMEOUT=86400
SWIFT_AUTH_MAX_SESSIONS=5
SWIFT_AUTH_SESSION_EVICTION=oldest

# Remember Me
SWIFT_AUTH_REMEMBER_ENABLED=true
SWIFT_AUTH_REMEMBER_TTL=1209600
SWIFT_AUTH_REMEMBER_ROTATE=true

# MFA
SWIFT_AUTH_MFA_ENABLED=false
SWIFT_AUTH_MFA_DRIVER=otp

# Email Verification
SWIFT_AUTH_REQUIRE_VERIFICATION=false

# Account Lockout
SWIFT_AUTH_LOCKOUT_ENABLED=true

# Table Prefix
SWIFT_AUTH_TABLE_PREFIX=swift-auth_

# Route Prefix
SWIFT_AUTH_ROUTE_PREFIX=swift-auth
```

## Testing

```bash
# Unit tests
vendor/bin/phpunit --testsuite Unit

# Feature tests (requires test environment setup)
vendor/bin/phpunit --testsuite Feature
```

## Best Practices

### 1. Use Environment Variables

Never hardcode sensitive values in config. Use `.env`:

```bash
SWIFT_AUTH_MFA_ENABLED=true
SWIFT_AUTH_MAX_SESSIONS=3
```

### 2. Customize Table Prefix

Avoid naming conflicts by setting a unique prefix:

```bash
SWIFT_AUTH_TABLE_PREFIX=myapp_auth_
```

### 3. Enable Account Lockout

Always enable in production to prevent brute-force attacks:

```php
'account_lockout' => ['enabled' => true]
```

### 4. Set Strong Password Requirements

For sensitive applications:

```php
'password_min_length' => 12,
'password_requirements' => [
    'require_mixed_case' => true,
    'require_numbers' => true,
    'require_symbols' => true,
],
```

### 5. Limit Concurrent Sessions

Prevent session hijacking:

```php
'session_limits' => [
    'max_sessions' => 3,
    'eviction' => 'oldest',
],
```

### 6. Use HTTPS in Production

Enable secure cookies:

```bash
SWIFT_AUTH_REMEMBER_SECURE=true
```

### 7. Regular Session Cleanup

The package automatically schedules cleanup. Ensure your scheduler is running:

```bash
* * * * * php artisan schedule:run >> /dev/null 2>&1
```

## Troubleshooting

### Session Not Persisting

-   Verify `SESSION_DRIVER` in `.env` is set to `database`, `redis`, or another persistent driver (not `array`).
-   Run `php artisan config:clear`.

### Rate Limit Errors

-   Clear rate limiters: manually delete keys from cache or restart cache driver.
-   Adjust limits in `config/swift-auth.php`.

### Middleware Not Working

-   Ensure middleware is registered in `app/Http/Kernel.php` (Laravel auto-discovery should handle this).
-   Check route group has correct middleware applied.

### MFA Not Triggering

-   Verify `SWIFT_AUTH_MFA_ENABLED=true`.
-   Check `mfa.driver` is set correctly (`otp` or `webauthn`).

## License

MIT License. See `LICENSE` for details.

## Authors

-   Gabriel Ruelas <gruelas@gruelas.com>
-   Raul Cruz <rcruz@teleurban.mx>
-   Guillermo Corona <gcorona@teleurban.mx>

## Support

-   **Issues**: [GitHub Issues](https://github.com/EquidnaMX/swift_auth/issues)
-   **Discussions**: [GitHub Discussions](https://github.com/EquidnaMX/swift_auth/discussions)

## Contributing

Contributions welcome! Please submit pull requests to the `dev` branch.

---

**SwiftAuth** — Simple, secure, session-based authentication for Laravel.
