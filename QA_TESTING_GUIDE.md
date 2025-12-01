# QA Testing Guide for SwiftAuth

**Version:** 1.0  
**Last Updated:** November 30, 2025  
**Target Audience:** QA Engineers implementing feature/integration tests

---

## 📚 Table of Contents

1. [Overview](#overview)
2. [Test Infrastructure](#test-infrastructure)
3. [Getting Started](#getting-started)
4. [Test Helpers](#test-helpers)
5. [Example Test Walkthrough](#example-test-walkthrough)
6. [Best Practices](#best-practices)
7. [CI/CD Integration](#cicd-integration)
8. [Troubleshooting](#troubleshooting)

---

## Overview

This guide provides everything you need to implement the feature and integration tests documented in `NON_UNIT_TEST_REQUESTS.md`.

**What's Already Done:**

-   ✅ 81 unit tests covering business logic
-   ✅ Test helper trait with common utilities
-   ✅ PHPUnit configuration with Feature test suite
-   ✅ GitHub Actions CI/CD workflow
-   ✅ Example test template

**Your Responsibility:**

-   Implement 80+ feature/integration test scenarios
-   Achieve 85%+ coverage for authentication flows
-   Ensure all tests pass in CI/CD pipeline

---

## Test Infrastructure

### Directory Structure

```
tests/
├── Unit/                          # ✅ Already implemented (81 tests)
│   ├── Models/
│   ├── Services/
│   ├── Middleware/
│   └── Traits/
├── Feature/                       # 🎯 Your work here
│   ├── Auth/
│   │   ├── LoginTest.php
│   │   ├── LogoutTest.php
│   │   ├── RateLimitingTest.php
│   │   └── AccountLockoutTest.php
│   ├── Password/
│   │   ├── RequestResetTest.php
│   │   └── ResetPasswordTest.php
│   ├── EmailVerification/
│   │   ├── SendVerificationTest.php
│   │   └── VerifyEmailTest.php
│   ├── User/
│   │   ├── CrudOperationsTest.php
│   │   └── RoleAssignmentTest.php
│   └── Role/
│       └── CrudOperationsTest.php
└── TestHelpers.php                # ✅ Utility trait ready to use
```

### Configuration Files

-   **`phpunit.xml`** - PHPUnit configuration with Unit and Feature test suites
-   **`.github/workflows/tests.yml`** - CI/CD workflow for GitHub Actions
-   **`tests/Feature/Auth/LoginTest.example.php`** - Example template to copy

---

## Getting Started

### 1. Environment Setup

```bash
# Install dependencies
composer install

# Copy test environment file
cp .env.testing.example .env.testing

# Edit .env.testing with test database credentials
# DB_DATABASE=swift_auth_test
# DB_USERNAME=root
# DB_PASSWORD=secret

# Create test database
mysql -u root -p -e "CREATE DATABASE swift_auth_test"

# Run migrations
php artisan migrate --env=testing
```

### 2. Run Existing Tests

```bash
# Run unit tests (should pass)
./vendor/bin/phpunit --testsuite Unit

# Run feature tests (empty for now)
./vendor/bin/phpunit --testsuite Feature

# Run all tests
./vendor/bin/phpunit
```

### 3. Create Your First Feature Test

```bash
# Copy the example template
cp tests/Feature/Auth/LoginTest.example.php tests/Feature/Auth/LoginTest.php

# Remove .example suffix and run
./vendor/bin/phpunit --filter LoginTest
```

---

## Test Helpers

The `TestHelpers` trait provides utilities for creating test data and assertions.

### Usage

```php
use Equidna\SwiftAuth\Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyFeatureTest extends TestCase
{
    use RefreshDatabase;  // Reset database between tests
    use TestHelpers;       // Access helper methods

    public function test_example(): void
    {
        // Use helpers
        $user = $this->createTestUser(['email' => 'test@example.com']);
        $admin = $this->createAdminUser();

        // Make assertions
        $this->assertUserHasRole($admin, 'admin');
    }
}
```

### Available Helper Methods

#### Creating Test Data

```php
// Create a basic test user
$user = $this->createTestUser([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password123'),
]);

// Create a user with specific roles
$editor = $this->createTestUserWithRoles(['editor', 'author']);

// Create an admin user (has 'sw-admin' permission)
$admin = $this->createAdminUser();

// Create a role
$role = $this->createTestRole([
    'name' => 'moderator',
    'actions' => ['posts.moderate', 'comments.delete'],
]);

// Create a locked user
$locked = $this->createLockedUser($minutesLocked = 15);

// Create an unverified user
$unverified = $this->createUnverifiedUser();
```

#### Test Assertions

```php
// Assert user has/doesn't have role
$this->assertUserHasRole($user, 'admin');
$this->assertUserDoesNotHaveRole($user, 'editor');

// Assert user can perform action
$this->assertUserCanPerformAction($user, 'users.create');

// Assert user is locked/unlocked
$this->assertUserIsLocked($user);
$this->assertUserIsNotLocked($user);

// Assert user has verification token
$this->assertUserHasVerificationToken($user);
```

#### Utility Methods

```php
// Generate tokens
$resetToken = $this->generatePasswordResetToken();
$verifyToken = $this->generateEmailVerificationToken();

// Simulate failed login attempts
$user = $this->simulateFailedLoginAttempts($user, $attempts = 3);
```

---

## Example Test Walkthrough

Let's implement a test for password reset flow (Priority 3 from NON_UNIT_TEST_REQUESTS.md).

### 1. Create Test File

```bash
touch tests/Feature/Password/RequestResetTest.php
```

### 2. Write the Test

```php
<?php

namespace Equidna\SwiftAuth\Tests\Feature\Password;

use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Models\PasswordResetToken;
use Equidna\SwiftAuth\Tests\TestHelpers;
use Equidna\BirdFlock\Facades\BirdFlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestResetTest extends TestCase
{
    use RefreshDatabase;
    use TestHelpers;

    public function test_request_reset_creates_token_in_database(): void
    {
        // Arrange
        $user = $this->createTestUser(['email' => 'user@example.com']);
        BirdFlock::fake(); // Mock bird-flock

        // Act
        $response = $this->postJson('/swift-auth/password/email', [
            'email' => 'user@example.com',
        ]);

        // Assert
        $response->assertStatus(200);

        $token = PasswordResetToken::where('email', 'user@example.com')->first();
        $this->assertNotNull($token);
        $this->assertNotNull($token->token); // Should be SHA256 hash
    }

    public function test_request_reset_dispatches_bird_flock_email(): void
    {
        // Arrange
        $user = $this->createTestUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act
        $this->postJson('/swift-auth/password/email', [
            'email' => 'user@example.com',
        ]);

        // Assert
        BirdFlock::assertDispatched(function ($plan) {
            return $plan->to === 'user@example.com'
                && $plan->subject === 'Password Reset Request';
        });
    }

    public function test_request_reset_returns_200_for_non_existent_email(): void
    {
        // Arrange
        BirdFlock::fake();

        // Act
        $response = $this->postJson('/swift-auth/password/email', [
            'email' => 'nonexistent@example.com',
        ]);

        // Assert - Should return 200 to prevent email enumeration
        $response->assertStatus(200);

        // But no token should be created
        $this->assertDatabaseMissing('swift-auth_PasswordResetTokens', [
            'email' => 'nonexistent@example.com',
        ]);
    }
}
```

### 3. Run the Test

```bash
./vendor/bin/phpunit --filter RequestResetTest
```

---

## Best Practices

### 1. Test Structure (AAA Pattern)

```php
public function test_description_of_behavior(): void
{
    // Arrange - Set up test data
    $user = $this->createTestUser();

    // Act - Perform the action
    $response = $this->postJson('/endpoint', ['data' => 'value']);

    // Assert - Verify the outcome
    $response->assertStatus(200);
    $this->assertDatabaseHas('table', ['field' => 'value']);
}
```

### 2. Test Naming

Use descriptive names that explain the scenario:

✅ **Good:**

```php
test_login_locks_account_after_five_failed_attempts()
test_password_reset_token_expires_after_fifteen_minutes()
test_email_verification_enforces_rate_limit_of_three_per_five_minutes()
```

❌ **Bad:**

```php
test_login()
test_reset()
test_verify()
```

### 3. Database Cleanup

Always use `RefreshDatabase` trait to ensure clean slate:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase; // ✅ Resets DB between tests
}
```

### 4. Mock External Services

```php
use Equidna\BirdFlock\Facades\BirdFlock;
use Illuminate\Support\Facades\Http;

// Mock bird-flock
BirdFlock::fake();

// Mock HTTP requests
Http::fake([
    'api.example.com/*' => Http::response(['success' => true], 200),
]);
```

### 5. Assert HTTP Responses

```php
$response->assertStatus(200);
$response->assertJson(['status' => 'success']);
$response->assertJsonStructure(['data' => ['user' => ['id', 'email']]]);
$response->assertJsonValidationErrors(['email']);
$response->assertHeader('Content-Type', 'application/json');
```

### 6. Assert Database State

```php
$this->assertDatabaseHas('swift-auth_Users', [
    'email' => 'user@example.com',
    'failed_login_attempts' => 0,
]);

$this->assertDatabaseMissing('swift-auth_PasswordResetTokens', [
    'email' => 'user@example.com',
]);

$this->assertDatabaseCount('swift-auth_Roles', 5);
```

---

## CI/CD Integration

### GitHub Actions Workflow

The `.github/workflows/tests.yml` file runs tests automatically on push/PR:

**Matrix Testing:**

-   PHP 8.2, 8.3, 8.4
-   MySQL 8.0
-   Redis 7

**Jobs:**

1. **unit-tests** - Fast unit tests (no DB required)
2. **feature-tests** - Full integration tests with MySQL + Redis
3. **static-analysis** - PHPStan level 5
4. **coding-standards** - PHPCS PSR-12

### Local CI Simulation

```bash
# Run what CI runs
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature --coverage-clover coverage.xml
./vendor/bin/phpstan analyse --memory-limit=1G
./vendor/bin/phpcs --standard=phpcs.xml src/
```

---

## Troubleshooting

### Common Issues

#### "Class TestCase not found"

**Solution:** Ensure you're extending Laravel's TestCase:

```php
use Tests\TestCase; // ✅ Laravel TestCase
// NOT: use PHPUnit\Framework\TestCase; ❌
```

#### "Target class [config] does not exist"

**Solution:** You're trying to run feature tests as unit tests. Use:

```bash
./vendor/bin/phpunit --testsuite Feature
```

#### "SQLSTATE[HY000]: General error: 1 no such table"

**Solution:** Run migrations for test environment:

```bash
php artisan migrate --env=testing
```

#### "Class 'Mockery' not found"

**Solution:** Install Mockery:

```bash
composer require --dev mockery/mockery
```

#### Tests are slow

**Solution:** Use in-memory SQLite for faster tests:

```bash
# .env.testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

---

## Quick Reference Card

### Test Creation Checklist

-   [ ] File in correct `tests/Feature/**/` directory
-   [ ] Class extends `Tests\TestCase`
-   [ ] Uses `RefreshDatabase` trait
-   [ ] Uses `TestHelpers` trait
-   [ ] Test methods start with `test_` or have `@test` annotation
-   [ ] Follows AAA pattern (Arrange, Act, Assert)
-   [ ] Descriptive test name
-   [ ] Mocks external services
-   [ ] Asserts HTTP response and database state
-   [ ] Includes meaningful failure messages

### Common Patterns

```php
// Authentication testing
$this->actingAs($user)->postJson('/endpoint', $data);
$this->assertAuthenticated();
$this->assertAuthenticatedAs($user);
$this->assertGuest();

// Rate limiting
RateLimiter::shouldReceive('tooManyAttempts')->andReturn(true);
RateLimiter::shouldReceive('availableIn')->andReturn(60);

// Time manipulation
$this->travelTo(now()->addMinutes(30));
$this->travel(30)->minutes();

// Event/queue assertions
Event::fake();
Queue::fake();
Event::assertDispatched(UserCreated::class);
Queue::assertPushed(SendEmailJob::class);
```

---

## Support & Resources

**Documentation:**

-   `NON_UNIT_TEST_REQUESTS.md` - Complete test scenario specification
-   `tests/Feature/Auth/LoginTest.example.php` - Example template
-   Laravel Testing Docs: https://laravel.com/docs/11.x/testing

**Commands:**

```bash
# Run specific test
./vendor/bin/phpunit --filter test_login_with_valid_credentials

# Run test file
./vendor/bin/phpunit tests/Feature/Auth/LoginTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/

# Run in parallel (faster)
php artisan test --parallel
```

**Questions:** Tag `@testing-core` in your PR or Slack channel.

---

**Good luck with your testing! 🧪**
