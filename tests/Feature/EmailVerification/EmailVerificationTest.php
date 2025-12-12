<?php

/**
 * Feature tests for email verification flow.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Feature\EmailVerification
 * @author    QA Team
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests\Feature\EmailVerification;

use Equidna\BirdFlock\Facades\BirdFlock;
use Equidna\SwiftAuth\Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Equidna\SwiftAuth\Tests\TestCase;

/**
 * Feature tests for email verification flow.
 *
 * Reference: NON_UNIT_TEST_REQUESTS.md - Priority 4: Email Verification
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    use TestHelpers;

    /**
     * Test email verification request sends email.
     *
     * Scenario: POST /swift-auth/email/send dispatches verification email
     */
    public function test_email_verification_request_sends_email(): void
    {
        // Arrange
        $user = $this->createUnverifiedUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act
        $response = $this->postJson('/swift-auth/email/send', [
            'email' => 'user@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        BirdFlock::assertDispatched(function ($plan) {
            return $plan->to === 'user@example.com'
                && str_contains($plan->subject, 'Verify');
        });
    }

    /**
     * Test email verification request creates token.
     *
     * Scenario: POST /swift-auth/email/send creates verification token
     */
    public function test_email_verification_request_creates_token(): void
    {
        // Arrange
        $user = $this->createUnverifiedUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act
        $this->postJson('/swift-auth/email/send', [
            'email' => 'user@example.com',
        ]);

        // Assert
        $user->refresh();
        $this->assertNotNull($user->email_verification_token);
        $this->assertNotNull($user->email_verification_sent_at);
    }

    /**
     * Test email verification with valid token marks email as verified.
     *
     * Scenario: GET /swift-auth/email/verify/{token} verifies email
     */
    public function test_email_verification_with_valid_token_marks_email_verified(): void
    {
        // Arrange
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        $user = $this->createUnverifiedUser([
            'email' => 'user@example.com',
            'email_verification_token' => $hashedToken,
            'email_verification_sent_at' => now(),
        ]);

        // Act
        $response = $this->getJson("/swift-auth/email/verify/{$rawToken}?email=user@example.com");

        // Assert
        $response->assertStatus(200);
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_token);
    }

    /**
     * Test email verification with invalid token fails.
     *
     * Scenario: GET /swift-auth/email/verify/{token} with invalid token returns 400
     */
    public function test_email_verification_with_invalid_token_fails(): void
    {
        // Arrange
        $user = $this->createUnverifiedUser([
            'email' => 'user@example.com',
            'email_verification_token' => hash('sha256', 'valid-token'),
            'email_verification_sent_at' => now(),
        ]);

        // Act
        $response = $this->getJson('/swift-auth/email/verify/invalid-token?email=user@example.com');

        // Assert
        $response->assertStatus(400);
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /**
     * Test email verification with expired token fails.
     *
     * Scenario: GET /swift-auth/email/verify/{token} with expired token returns 400
     */
    public function test_email_verification_with_expired_token_fails(): void
    {
        // Arrange
        config(['swift-auth.email_verification.token_ttl' => 86400]); // 24 hours
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        $user = $this->createUnverifiedUser([
            'email' => 'user@example.com',
            'email_verification_token' => $hashedToken,
            'email_verification_sent_at' => now()->subHours(25), // Expired
        ]);

        // Act
        $response = $this->getJson("/swift-auth/email/verify/{$rawToken}?email=user@example.com");

        // Assert
        $response->assertStatus(400);
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /**
     * Test email verification returns error for already verified email.
     *
     * Scenario: POST /swift-auth/email/send for already verified email returns 400
     */
    public function test_email_verification_returns_error_for_already_verified_email(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/email/send', [
            'email' => 'user@example.com',
        ]);

        // Assert
        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Email already verified.',
        ]);
    }

    /**
     * Test email verification returns error for non-existent user.
     *
     * Scenario: POST /swift-auth/email/send for non-existent email returns 404
     */
    public function test_email_verification_returns_error_for_non_existent_user(): void
    {
        // Act
        $response = $this->postJson('/swift-auth/email/send', [
            'email' => 'nonexistent@example.com',
        ]);

        // Assert
        $response->assertStatus(404);
    }

    /**
     * Test email verification rate limiting.
     *
     * Scenario: POST /swift-auth/email/send enforces rate limiting
     */
    public function test_email_verification_enforces_rate_limiting(): void
    {
        // Arrange
        config(['swift-auth.email_verification.resend_rate_limit.attempts' => 3]);
        config(['swift-auth.email_verification.resend_rate_limit.decay_seconds' => 300]);
        $user = $this->createUnverifiedUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act - Make attempts up to limit
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/swift-auth/email/send', ['email' => 'user@example.com']);
        }

        // Fourth attempt should be rate limited
        $response = $this->postJson('/swift-auth/email/send', ['email' => 'user@example.com']);

        // Assert
        $response->assertStatus(429);
    }

    /**
     * Test email verification validates email format.
     *
     * Scenario: POST /swift-auth/email/send validates email format
     */
    public function test_email_verification_validates_email_format(): void
    {
        // Act
        $response = $this->postJson('/swift-auth/email/send', [
            'email' => 'invalid-email',
        ]);

        // Assert
        $response->assertStatus(400);
    }
}
