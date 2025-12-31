# API Documentation

SwiftAuth provides "Context-Aware" endpoints. They return **JSON** if `Accept: application/json` is requested, or **Inertia/Blade** views for browser requests.

**Base URL Prefix:** `/swift-auth` (configurable via `SWIFT_AUTH_ROUTE_PREFIX`)

## Authentication

### Login

**POST** `/login`

Authenticates a user via email and password.

**Request Body:**

```json
{
    "email": "user@example.com",
    "password": "secret-password",
    "remember": true
}
```

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Login successful.",
    "data": {
        "user": { "id_user": 1, "email": "..." },
        "redirect_url": "/dashboard"
    }
}
```

### Logout

**POST** `/logout`

Terminates the current session.

**Response (200 OK):**

```json
{
    "success": true,
    "message": "Logged out successfully."
}
```

## Registration (If Enabled)

### Register User

**POST** `/users`

Registers a new user account.

**Request Body:**

```json
{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "StrongPassword1!",
    "password_confirmation": "StrongPassword1!"
}
```

**Response (201 Created):**

```json
{
    "success": true,
    "message": "Account created successfully."
}
```

## Password Management

### Send Reset Link

**POST** `/password`

Triggers a password reset email.

**Request Body:**

```json
{
    "email": "user@example.com"
}
```

### Reset Password

**POST** `/password/reset`

Completes the password reset process.

**Request Body:**

```json
{
    "email": "user@example.com",
    "token": "hashed-token-from-email",
    "password": "NewStrongPassword1!",
    "password_confirmation": "NewStrongPassword1!"
}
```

---

## API Token Authentication

SwiftAuth provides a built-in API token system for stateless authentication. Tokens are SHA-256 hashed, support ability/scope-based authorization, expiration, and usage tracking.

### Creating Tokens

Tokens are created programmatically via the `UserTokenService`:

```php
use Equidna\SwiftAuth\Classes\Auth\Services\UserTokenService;

$tokenService = app(UserTokenService::class);

$result = $tokenService->createToken(
    user: $user,
    name: 'Mobile App Token',
    abilities: ['posts:read', 'posts:create'],
    expiresAt: now()->addDays(30)
);

// Return plain token to user (only shown once)
$plainToken = $result['token'];
$tokenModel = $result['model'];
```

### Authenticating with Tokens

Include the token in the `Authorization` header:

```bash
Authorization: Bearer {your-plain-token}
```

### Token Abilities

-   **Wildcard:** Empty abilities array `[]` or `['*']` grants all permissions
-   **Scoped:** Specific abilities like `['posts:read', 'users:update']`
-   **Check:** Use `$token->can('ability')` to verify permissions

### Token Management

**Validate Token:**

```php
$token = $tokenService->validateToken($plainToken);
if ($token && !$token->isExpired()) {
    $user = $token->user;
}
```

**Check Abilities:**

```php
if ($tokenService->canPerformAction($plainToken, 'posts:delete')) {
    // Allow action
}
```

**Revoke Token:**

```php
$tokenService->revokeToken($tokenId);
$tokenService->revokeAllUserTokens($userId);
```

**Cleanup Expired:**

```bash
php artisan swift-auth:purge-expired-tokens
```

This command runs automatically every hour and includes UserToken cleanup.

### Security Notes

-   Tokens are hashed with SHA-256 before storage
-   Plain tokens are only shown once at creation
-   Expired tokens fail validation automatically
-   Usage is tracked via `last_used_at` timestamp
-   Foreign key cascade ensures tokens are deleted with users

---

## Rate Limiting

SwiftAuth implements comprehensive rate limiting to prevent abuse and protect against brute-force attacks. All rate limits return `429 Too Many Requests` when exceeded.

### Login Rate Limits

**Per Email:**

-   **Attempts:** 3 failed login attempts
-   **Window:** 5 minutes (300 seconds)
-   **Key:** `login:email:{sha256(email)}`

**Per IP Address:**

-   **Attempts:** 10 failed login attempts
-   **Window:** 5 minutes (300 seconds)
-   **Key:** `login:ip:{ip_address}`

**Response when exceeded:**

```json
{
    "message": "Too many login attempts. Please try again in X seconds.",
    "retry_after": 300
}
```

### Password Reset Rate Limits

**Per Email:**

-   **Attempts:** 3 reset requests
-   **Window:** 5 minutes (300 seconds)
-   **Key:** `password-reset:email:{sha256(email)}`

**Per IP Address:**

-   **Attempts:** 50 requests (soft limit)
-   **Window:** 5 minutes (300 seconds)
-   **Key:** `password-reset:ip:{ip_address}`

**Token Verification:**

-   **Attempts:** 5 verification attempts
-   **Window:** 1 hour (3600 seconds)
-   **Key:** `password-reset:verify:{sha256(email)}`

### Email Verification Rate Limits

**Per Email (Resend):**

-   **Attempts:** 3 resend requests
-   **Window:** 5 minutes (300 seconds)
-   **Key:** `email-verification:{sha256(email)}`

**Per IP Address:**

-   **Attempts:** 5 verification requests
-   **Window:** 1 minute (60 seconds)
-   **Key:** `email-verification:ip:{ip_address}`

### Best Practices

1. **Handle 429 Responses:** Check the `retry_after` header or JSON field
2. **Implement Exponential Backoff:** Increase wait time between retries
3. **Show User Feedback:** Display remaining time before retry is allowed
4. **Cache Tokens:** Don't request new tokens for every retry
5. **Monitor Logs:** SwiftAuth logs all rate limit violations

### Configuration

Rate limits are configurable via `config/swift-auth.php`:

```php
'login_rate_limits' => [
    'email' => [
        'attempts' => 3,
        'decay_seconds' => 300,
    ],
    'ip' => [
        'attempts' => 10,
        'decay_seconds' => 300,
    ],
],

'password_reset_rate_limit' => [
    'attempts' => 3,
    'decay_seconds' => 300,
],

'email_verification' => [
    'resend_rate_limit' => [
        'attempts' => 3,
        'decay_seconds' => 300,
    ],
    'ip_rate_limit' => [
        'attempts' => 5,
        'decay_seconds' => 60,
    ],
],
```

---

## WebAuthn / Passkeys

### Get Registration Options

**POST** `/webauthn/register/options`

**Headers:** `Authorization: Bearer <token>` or Session Cookie.

Returns public key options to initiate Passkey registration.

### Complete Registration

**POST** `/webauthn/register`

Verifies the authenticator response and stores the credential.

### Get Login Options

**POST** `/webauthn/login/options`

Returns challenge for Passkey login (can be user-agnostic or scoped to email).

### Complete Login

**POST** `/webauthn/login`

Verifies the signed challenge and logs the user in.
