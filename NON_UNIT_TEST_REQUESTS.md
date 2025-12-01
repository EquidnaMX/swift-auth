# Non-Unit Test Requests for SwiftAuth

> **Audience:** QA/Platform Team  
> **Purpose:** Document feature, integration, and E2E test scenarios for SwiftAuth package  
> **Created:** November 30, 2025  
> **Package Version:** 1.0.2+

## Overview

This document describes test scenarios that **require Laravel framework integration**, database access, HTTP testing, or other non-isolated environments. These tests are **outside the scope of unit testing** and must be implemented by the QA/Platform team.

Per the Agent Testing Scope policy, AI agents are restricted to unit tests only. The scenarios below require:

-   Laravel application bootstrapping
-   Database transactions (`RefreshDatabase`)
-   HTTP request/response testing
-   Queue/cache/session interactions
-   Multi-layer integration

---

## 🔴 Priority 1: Authentication Flow (CRITICAL)

### Login Flow Tests

**File:** `tests/Feature/Auth/LoginTest.php`

```php
// Scenarios to test:
- [ ] POST /swift-auth/login with valid credentials returns 200 and session
- [ ] POST /swift-auth/login with invalid credentials returns 401
- [ ] POST /swift-auth/login regenerates session ID on successful login
- [ ] POST /swift-auth/login resets failed_login_attempts on success
- [ ] POST /swift-auth/login increments failed_login_attempts on failure
- [ ] POST /swift-auth/login locks account after 5 failed attempts (configurable)
- [ ] POST /swift-auth/login rejects login when account is locked
- [ ] POST /swift-auth/login sends lockout email via bird-flock when locked
- [ ] POST /swift-auth/login validates CSRF token
- [ ] POST /swift-auth/login handles missing email field
- [ ] POST /swift-auth/login handles missing password field
- [ ] POST /swift-auth/login trims whitespace from email
- [ ] POST /swift-auth/login is case-insensitive for email
- [ ] POST /swift-auth/login logs audit trail on success
- [ ] POST /swift-auth/login logs security event on failure
```

### Logout Flow Tests

**File:** `tests/Feature/Auth/LogoutTest.php`

```php
// Scenarios:
- [ ] POST /swift-auth/logout clears session
- [ ] POST /swift-auth/logout invalidates session in database (if using DB sessions)
- [ ] POST /swift-auth/logout returns 200 for authenticated user
- [ ] POST /swift-auth/logout returns 401 for unauthenticated user
- [ ] POST /swift-auth/logout logs audit trail
```

### Session Security Tests

**File:** `tests/Feature/Auth/SessionSecurityTest.php`

```php
// Scenarios:
- [ ] Session ID changes on login (prevents fixation)
- [ ] Session expires after configured timeout
- [ ] Concurrent sessions are limited per user (if configured)
- [ ] Session contains user ID after authentication
- [ ] Session validation works across requests
```

---

## 🟠 Priority 2: Rate Limiting (HIGH)

### Authentication Rate Limiting

**File:** `tests/Feature/Auth/RateLimitingTest.php`

```php
// Scenarios:
- [ ] Login endpoint enforces 5 attempts per minute per IP
- [ ] Rate limit returns 429 after threshold exceeded
- [ ] Rate limit counter resets after decay period
- [ ] Rate limit uses Redis when configured
- [ ] Rate limit response includes Retry-After header
- [ ] Rate limit logs security events
```

### Password Reset Rate Limiting

**File:** `tests/Feature/Password/RateLimitingTest.php`

```php
// Scenarios:
- [ ] Password reset enforces 3 attempts per 5 minutes per email
- [ ] Rate limit applies even for non-existent emails (prevent enumeration)
- [ ] Rate limit works across multiple IPs for same email
```

### Email Verification Rate Limiting

**File:** `tests/Feature/EmailVerification/RateLimitingTest.php`

```php
// Scenarios:
- [ ] Email verification enforces 3 attempts per 5 minutes per email
- [ ] Rate limit counter increments only after email dispatch
- [ ] Rate limit applies to both existing and non-existent users
```

---

## 🟠 Priority 3: Password Reset Flow (HIGH)

### Password Reset Request

**File:** `tests/Feature/Password/RequestResetTest.php`

```php
// Scenarios:
- [ ] POST /swift-auth/password/email creates token in database
- [ ] POST /swift-auth/password/email hashes token with SHA256
- [ ] POST /swift-auth/password/email dispatches bird-flock email
- [ ] POST /swift-auth/password/email returns 200 even for non-existent email (security)
- [ ] POST /swift-auth/password/email logs request with IP address
- [ ] POST /swift-auth/password/email invalidates previous tokens
- [ ] POST /swift-auth/password/email token expires after 15 minutes
- [ ] POST /swift-auth/password/email validates email format
- [ ] POST /swift-auth/password/email handles bird-flock dispatch failure gracefully
```

### Password Reset Completion

**File:** `tests/Feature/Password/ResetPasswordTest.php`

```php
// Scenarios:
- [ ] POST /swift-auth/password/reset with valid token updates password
- [ ] POST /swift-auth/password/reset invalidates token after use
- [ ] POST /swift-auth/password/reset validates token exists in database
- [ ] POST /swift-auth/password/reset validates token not expired (15 min TTL)
- [ ] POST /swift-auth/password/reset enforces password strength requirements
- [ ] POST /swift-auth/password/reset returns 400 for expired token
- [ ] POST /swift-auth/password/reset returns 400 for invalid token
- [ ] POST /swift-auth/password/reset logs successful password change
- [ ] POST /swift-auth/password/reset prevents token reuse
```

---

## 🟡 Priority 4: Email Verification Flow (MEDIUM)

### Send Verification Email

**File:** `tests/Feature/EmailVerification/SendVerificationTest.php`

```php
// Scenarios:
- [ ] POST /swift-auth/email/send creates verification token
- [ ] POST /swift-auth/email/send hashes token with SHA256
- [ ] POST /swift-auth/email/send dispatches bird-flock email
- [ ] POST /swift-auth/email/send returns 404 for non-existent user
- [ ] POST /swift-auth/email/send returns 400 for already-verified email
- [ ] POST /swift-auth/email/send enforces rate limit (3/5min)
- [ ] POST /swift-auth/email/send validates email format
- [ ] POST /swift-auth/email/send logs send attempt
- [ ] POST /swift-auth/email/send handles bird-flock failure
```

### Verify Email

**File:** `tests/Feature/EmailVerification/VerifyEmailTest.php`

```php
// Scenarios:
- [ ] GET /swift-auth/email/verify/{token}?email=... marks email as verified
- [ ] GET /swift-auth/email/verify/{token} clears verification token
- [ ] GET /swift-auth/email/verify/{token} validates token not expired (24hr TTL)
- [ ] GET /swift-auth/email/verify/{token} returns 400 for invalid token
- [ ] GET /swift-auth/email/verify/{token} returns 400 for expired token
- [ ] GET /swift-auth/email/verify/{token} returns 400 for missing email param
- [ ] GET /swift-auth/email/verify/{token} logs successful verification
- [ ] GET /swift-auth/email/verify/{token} prevents token reuse
```

---

## 🟡 Priority 5: Account Lockout (MEDIUM)

### Lockout Mechanism

**File:** `tests/Feature/Auth/AccountLockoutTest.php`

```php
// Scenarios:
- [ ] Account locks after 5 consecutive failed logins (configurable)
- [ ] Locked account rejects login attempts until lockout expires
- [ ] Locked account sends notification email via bird-flock
- [ ] Lockout duration is 15 minutes (configurable)
- [ ] Failed attempt counter resets on successful login
- [ ] Failed attempt counter resets after 1 hour of no attempts (configurable)
- [ ] Lockout status includes locked_until timestamp
- [ ] php artisan swift-auth:unlock-user {email} unlocks account
- [ ] Unlock command resets failed_login_attempts to 0
- [ ] Unlock command logs admin action
- [ ] Lockout applies per-email, not per-IP
```

---

## 🟡 Priority 6: User CRUD Operations (MEDIUM)

### User Management

**File:** `tests/Feature/User/CrudOperationsTest.php`

```php
// Scenarios:
- [ ] GET /swift-auth/users returns paginated user list
- [ ] GET /swift-auth/users supports search by name/email
- [ ] GET /swift-auth/users returns 401 for unauthenticated
- [ ] POST /swift-auth/users creates new user with hashed password
- [ ] POST /swift-auth/users validates required fields
- [ ] POST /swift-auth/users enforces unique email constraint
- [ ] POST /swift-auth/users assigns default role if configured
- [ ] GET /swift-auth/users/{id} returns single user with roles
- [ ] PUT /swift-auth/users/{id} updates user data
- [ ] PUT /swift-auth/users/{id} does not update password unless provided
- [ ] DELETE /swift-auth/users/{id} soft deletes user
- [ ] DELETE /swift-auth/users/{id} prevents self-deletion
```

### Role Assignment

**File:** `tests/Feature/User/RoleAssignmentTest.php`

```php
// Scenarios:
- [ ] POST /swift-auth/users/{id}/roles assigns role to user
- [ ] DELETE /swift-auth/users/{id}/roles/{roleId} removes role from user
- [ ] User can have multiple roles
- [ ] Role assignment validates role exists
- [ ] Role assignment prevents duplicates
```

---

## 🟢 Priority 7: Role CRUD Operations (LOW)

### Role Management

**File:** `tests/Feature/Role/CrudOperationsTest.php`

```php
// Scenarios:
- [ ] GET /swift-auth/roles returns all roles with actions
- [ ] POST /swift-auth/roles creates new role
- [ ] POST /swift-auth/roles validates actions array format
- [ ] POST /swift-auth/roles enforces unique role name
- [ ] GET /swift-auth/roles/{id} returns single role with users
- [ ] PUT /swift-auth/roles/{id} updates role data
- [ ] DELETE /swift-auth/roles/{id} soft deletes role
- [ ] Deleting role does not cascade delete users (orphans users)
```

---

## 🟢 Priority 8: Authorization Middleware (LOW)

### CanPerformAction Middleware

**File:** `tests/Feature/Authorization/CanPerformActionTest.php`

```php
// Scenarios:
- [ ] Middleware allows request when user has required action
- [ ] Middleware returns 403 when user lacks action
- [ ] Middleware returns 401 when user not authenticated
- [ ] Middleware validates action format (dot notation)
- [ ] Middleware checks all user roles for action
- [ ] Middleware is case-insensitive for action names
```

---

## 🔵 Priority 9: Bird-Flock Integration (E2E)

### Email Delivery via Bird-Flock

**File:** `tests/Integration/BirdFlock/EmailDeliveryTest.php`

```php
// Scenarios (requires bird-flock test mode):
- [ ] Password reset email dispatches via bird-flock queue
- [ ] Email verification dispatches via bird-flock queue
- [ ] Account lockout notification dispatches via bird-flock queue
- [ ] Bird-flock idempotency prevents duplicate sends
- [ ] Bird-flock dead-letter queue captures failed sends
- [ ] Bird-flock circuit breaker activates on provider failures
- [ ] Bird-flock retry logic uses exponential backoff
```

---

## 🔵 Priority 10: Database Integrity (INTEGRATION)

### Schema Validation

**File:** `tests/Integration/Database/SchemaTest.php`

```php
// Scenarios:
- [ ] swift-auth_Users table has all required columns
- [ ] swift-auth_Roles table has all required columns
- [ ] swift-auth_UserRole pivot table exists with composite key
- [ ] swift-auth_PasswordResetTokens table has correct indexes
- [ ] Email columns have unique constraints
- [ ] Soft delete columns exist (deleted_at)
- [ ] Timestamp columns default to NULL
```

### Data Integrity

**File:** `tests/Integration/Database/IntegrityTest.php`

```php
// Scenarios:
- [ ] Deleting role does not delete associated users
- [ ] Deleting user cascades to UserRole pivot
- [ ] Email uniqueness is enforced at database level
- [ ] Password reset tokens expire via database query
- [ ] Email verification tokens expire via database query
```

---

## Testing Environment Setup

### Required Configuration

```php
// config/swift-auth.php (test environment)
'email_verification' => [
    'required' => false, // Don't block tests
    'token_ttl' => 60,   // 1 minute for faster tests
    'resend_rate_limit' => [
        'attempts' => 10,
        'decay_seconds' => 60,
    ],
],

'account_lockout' => [
    'enabled' => true,
    'max_attempts' => 3, // Lower for faster tests
    'lockout_duration' => 60,
    'reset_after' => 120,
],

'bird_flock' => [
    'enabled' => true,
    'from_email' => 'test@swift-auth.test',
    'from_name' => 'SwiftAuth Test',
],
```

### Required Traits

```php
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
```

### Bird-Flock Test Mode

```php
// Enable bird-flock test mode in tests/TestCase.php
protected function setUp(): void
{
    parent::setUp();

    // Mock bird-flock to prevent actual email sends
    BirdFlock::fake();
}

// Assert email was sent
BirdFlock::assertDispatched(function (FlightPlan $plan) {
    return $plan->to === 'user@example.com'
        && $plan->subject === 'Password Reset Request';
});
```

---

## Coverage Goals

-   **Controllers:** 90%+ line coverage
-   **Authentication Flow:** 100% critical path coverage
-   **Rate Limiting:** 100% enforcement coverage
-   **Email Notifications:** 100% dispatch coverage
-   **Database Integrity:** 100% constraint coverage

---

## Execution Commands

```bash
# Run all feature tests
./vendor/bin/phpunit --testsuite Feature

# Run specific priority
./vendor/bin/phpunit --group priority-1

# Run with coverage
./vendor/bin/phpunit --testsuite Feature --coverage-html coverage/

# Run parallel tests (Laravel 11+)
php artisan test --parallel
```

---

## CI/CD Integration

```yaml
# .github/workflows/feature-tests.yml
name: feature-tests
on:
    pull_request:
        paths:
            - "src/**"
            - "tests/Feature/**"
jobs:
    tests:
        runs-on: ubuntu-latest
        services:
            mysql:
                image: mysql:8.0
                env:
                    MYSQL_DATABASE: swift_auth_test
                    MYSQL_ROOT_PASSWORD: secret
            redis:
                image: redis:7
        steps:
            - uses: actions/checkout@v4
            - uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.3"
                  extensions: mysql, redis
            - run: composer install
            - run: php artisan migrate --env=testing
            - run: ./vendor/bin/phpunit --testsuite Feature
```

---

## Notes for QA Team

1. **Test Data:** Use factories for consistent test data generation
2. **Isolation:** Each test should clean up after itself (use `RefreshDatabase`)
3. **Mocking:** Mock bird-flock in tests to prevent actual email sends
4. **Performance:** Keep individual tests under 500ms
5. **Assertions:** Use descriptive assertion messages for debugging
6. **Documentation:** Add comments for complex test scenarios

**Estimated Effort:** 60-80 hours total  
**Priority 1-3 Effort:** 40 hours  
**Dependencies:** Laravel 11+, MySQL/PostgreSQL, Redis, Bird-Flock package

---

**Contact:** @testing-core for questions  
**Last Updated:** November 30, 2025
