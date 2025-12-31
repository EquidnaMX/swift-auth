# Business Logic & Core Processes

## 1. User Login Flow

Authenticated access to the system.

**Process:**

1.  User submits credentials (Email + Password).
2.  Rate limits are checked (IP & Email).
3.  Credentials validated against Database.
4.  **Check:** Is MFA enabled and configured for user?
    -   **Yes:** Store partial session, issue MFA challenge, return "MFA Required" response.
    -   **No:** Create full session, log user in.
5.  **Check:** Is Concurrent Session Limit enabled?
    -   **Yes:** Check active sessions count.
    -   If limit exceeded, evict oldest/newest session based on policy.
6.  Return auth token or session cookie.

```mermaid
sequenceDiagram
    User->>AuthController: POST /login
    AuthController->>RateLimiter: Check Limits
    AuthController->>UserRepository: Validate Credentials

    alt Invalid
        AuthController-->>User: 422 Error
    else Valid
        AuthController->>MfaService: Check MFA Status

        opt MFA Required
            MfaService-->>AuthController: Challenge Info
            AuthController-->>User: 200 OK (MFA Pending)
            User->>MfaController: Submit OTP/WebAuthn
        end

        AuthController->>SessionManager: Create Session

        opt Session Limit Reached
            SessionManager->>SessionStore: Evict Oldest
        end

        AuthController-->>User: 200 OK (Logged In)
    end
```

## 2. Password Reset Flow

Secure account recovery.

**Process:**

1.  User requests reset for Email.
2.  Rate limit check.
3.  Token generated (Server-side hash stored).
4.  Notification dispatched (BirdFlock).
5.  User clicks link → validates token → enters new password.
6.  Password updated, User logged in (optional flow), Tokens cleared.

## 3. Account Lockout

Brute-force protection mechanism.

**Rules:**

-   **Trigger:** 5 failed attempts (configurable) within 5 minutes.
-   **Action:** Lock account for 15 minutes.
-   **Notification:** Send "Account Locked" email via NotificationService.
-   **Resolution:** Wait for timer expiration or admin manual unlock (`swift-auth:unlock-user`).

## 4. API Token Authentication Flow

Stateless authentication for API access using UserToken.

**Process:**

1.  Client requests token creation (admin panel or programmatic).
2.  `UserTokenService` generates 64-character random token.
3.  Token hashed with SHA-256 before database storage.
4.  Plain token returned **once** to client.
5.  Client includes token in `Authorization: Bearer {token}` header.
6.  Middleware validates token:
    -   Hash incoming token
    -   Lookup in `UserTokens` table
    -   Check expiration
    -   Verify abilities/scopes
7.  If valid, attach `User` to request; otherwise return 401 Unauthorized.
8.  Update `last_used_at` timestamp on successful validation.

**Token Structure:**

```php
[
    'id_user_token' => 123,
    'id_user' => 45,
    'name' => 'Mobile App Token',
    'hashed_token' => 'sha256...',
    'abilities' => ['posts:read', 'posts:create'], // Empty = wildcard
    'expires_at' => '2025-01-30 12:00:00',
    'last_used_at' => '2025-12-30 10:15:00',
]
```

**Security Features:**

-   SHA-256 hashing (matches `RememberToken` pattern)
-   Optional expiration enforcement
-   Ability-based authorization
-   Usage tracking for audit trails
-   Automatic cleanup via scheduled command
-   Cascade deletion with user accounts
