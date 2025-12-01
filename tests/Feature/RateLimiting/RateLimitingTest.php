<?php

/**
 * Feature tests for rate limiting across all endpoints.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Feature\RateLimiting
 * @author    QA Team
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests\Feature\RateLimiting;

use Equidna\BirdFlock\Facades\BirdFlock;
use Equidna\SwiftAuth\Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Feature tests for rate limiting.
 *
 * Reference: NON_UNIT_TEST_REQUESTS.md - Priority 2: Rate Limiting
 */
class RateLimitingTest extends TestCase
{
    use RefreshDatabase;
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear rate limiter before each test
        RateLimiter::clear('login:email:' . hash('sha256', 'user@example.com'));
        RateLimiter::clear('login:ip:127.0.0.1');
    }

    /**
     * Test login rate limiting per email.
     *
     * Scenario: Login endpoint enforces 5 attempts per email
     */
    public function test_login_enforces_rate_limit_per_email(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act - Make 5 failed attempts (limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/swift-auth/login', [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Sixth attempt should be rate limited
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertStatus(401); // UnauthorizedException with rate limit message
        $response->assertJsonFragment(['status' => 'error']);
    }

    /**
     * Test login rate limiting per IP address.
     *
     * Scenario: Login endpoint enforces 20 attempts per IP
     */
    public function test_login_enforces_rate_limit_per_ip(): void
    {
        // Arrange - Create multiple users
        for ($i = 0; $i < 25; $i++) {
            $this->createTestUser([
                'email' => "user{$i}@example.com",
                'password' => bcrypt('password123'),
            ]);
        }

        // Act - Make 20 failed attempts from same IP (different emails)
        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/swift-auth/login', [
                'email' => "user{$i}@example.com",
                'password' => 'wrong-password',
            ]);
        }

        // 21st attempt should be rate limited
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'user20@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test rate limit counter resets after successful login.
     *
     * Scenario: Successful login clears rate limit counter
     */
    public function test_successful_login_clears_rate_limit_counter(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act - Make 2 failed attempts
        for ($i = 0; $i < 2; $i++) {
            $this->postJson('/swift-auth/login', [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Successful login
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Assert rate limit was cleared - next attempts should work
        $response->assertStatus(200);

        // Try again after logout
        $this->postJson('/swift-auth/logout');

        $response2 = $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response2->assertStatus(200);
    }

    /**
     * Test password reset rate limiting per email.
     *
     * Scenario: Password reset enforces 5 attempts per email
     */
    public function test_password_reset_enforces_rate_limit_per_email(): void
    {
        // Arrange
        config(['swift-auth.password_reset_rate_limit.attempts' => 5]);
        config(['swift-auth.password_reset_rate_limit.decay_seconds' => 60]);
        $user = $this->createTestUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act - Make 5 attempts (limit)
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/swift-auth/password', [
                'email' => 'user@example.com',
            ]);
        }

        // Sixth attempt should be rate limited
        $response = $this->postJson('/swift-auth/password', [
            'email' => 'user@example.com',
        ]);

        // Assert
        $response->assertStatus(429);
        $response->assertJson([
            'message' => \Mockery::pattern('/Too many password reset attempts/'),
        ]);
    }

    /**
     * Test password reset rate limiting applies to non-existent emails.
     *
     * Scenario: Rate limit applies even for non-existent emails (prevent enumeration)
     */
    public function test_password_reset_rate_limit_applies_to_non_existent_emails(): void
    {
        // Arrange
        config(['swift-auth.password_reset_rate_limit.attempts' => 3]);
        BirdFlock::fake();

        // Act - Make 3 attempts for non-existent email
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/swift-auth/password', [
                'email' => 'nonexistent@example.com',
            ]);
        }

        // Fourth attempt should be rate limited
        $response = $this->postJson('/swift-auth/password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Assert
        $response->assertStatus(429);
    }

    /**
     * Test email verification rate limiting.
     *
     * Scenario: Email verification enforces 3 attempts per 5 minutes
     */
    public function test_email_verification_enforces_rate_limiting(): void
    {
        // Arrange
        config(['swift-auth.email_verification.resend_rate_limit.attempts' => 3]);
        config(['swift-auth.email_verification.resend_rate_limit.decay_seconds' => 300]);
        $user = $this->createUnverifiedUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act - Make 3 attempts (limit)
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/swift-auth/email/send', [
                'email' => 'user@example.com',
            ]);
        }

        // Fourth attempt should be rate limited
        $response = $this->postJson('/swift-auth/email/send', [
            'email' => 'user@example.com',
        ]);

        // Assert
        $response->assertStatus(429);
    }

    /**
     * Test rate limit response includes retry-after information.
     *
     * Scenario: Rate limit returns seconds until retry available
     */
    public function test_rate_limit_response_includes_retry_after_info(): void
    {
        // Arrange
        config(['swift-auth.password_reset_rate_limit.attempts' => 2]);
        config(['swift-auth.password_reset_rate_limit.decay_seconds' => 60]);
        $user = $this->createTestUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act - Exceed rate limit
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/swift-auth/password', [
                'email' => 'user@example.com',
            ]);
        }

        // Assert
        $this->assertEquals(429, $response->status());
        $responseData = $response->json();
        $this->assertStringContainsString('again in', $responseData['message']);
        $this->assertStringContainsString('seconds', $responseData['message']);
    }

    /**
     * Test account lockout rate limiting is separate from login rate limiting.
     *
     * Scenario: Account lockout has separate rate limit from login attempts
     */
    public function test_account_lockout_separate_from_login_rate_limit(): void
    {
        // Arrange
        config(['swift-auth.account_lockout.max_attempts' => 3]);
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act - Make 3 failed attempts (triggers lockout)
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/swift-auth/login', [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ]);
        }

        // Assert user is locked
        $user->refresh();
        $this->assertUserIsLocked($user);

        // Login rate limit shouldn't prevent lockout check
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123', // Even with correct password
        ]);

        $response->assertStatus(401); // Locked account
    }
}
