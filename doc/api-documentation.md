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
