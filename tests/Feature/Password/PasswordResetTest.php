<?php

/**
 * Feature tests for password reset flow.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Feature\Password
 * @author    QA Team
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests\Feature\Password;

use Equidna\BirdFlock\Facades\BirdFlock;
use Equidna\SwiftAuth\Models\PasswordResetToken;
use Equidna\SwiftAuth\Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature tests for password reset flow.
 *
 * Reference: NON_UNIT_TEST_REQUESTS.md - Priority 3: Password Reset
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;
    use TestHelpers;

    /**
     * Test password reset request creates token.
     *
     * Scenario: POST /swift-auth/password creates token in database
     */
    public function test_password_reset_request_creates_token_in_database(): void
    {
        // Arrange
        $user = $this->createTestUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act
        $response = $this->postJson('/swift-auth/password', [
            'email' => 'user@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('swift-auth_PasswordResetTokens', [
            'email' => 'user@example.com',
        ]);

        $token = PasswordResetToken::where('email', 'user@example.com')->first();
        $this->assertNotNull($token->token);
    }

    /**
     * Test password reset request dispatches bird-flock email.
     *
     * Scenario: POST /swift-auth/password dispatches email via bird-flock
     */
    public function test_password_reset_request_dispatches_bird_flock_email(): void
    {
        // Arrange
        $user = $this->createTestUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act
        $this->postJson('/swift-auth/password', [
            'email' => 'user@example.com',
        ]);

        // Assert
        BirdFlock::assertDispatched(function ($plan) {
            return $plan->to === 'user@example.com'
                && str_contains($plan->subject, 'Password Reset');
        });
    }

    /**
     * Test password reset returns 200 for non-existent email (prevent enumeration).
     *
     * Scenario: POST /swift-auth/password returns 200 for non-existent email
     */
    public function test_password_reset_returns_success_for_non_existent_email(): void
    {
        // Arrange
        BirdFlock::fake();

        // Act
        $response = $this->postJson('/swift-auth/password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Password reset instructions sent (if the email exists).',
        ]);

        // But no token should be created
        $this->assertDatabaseMissing('swift-auth_PasswordResetTokens', [
            'email' => 'nonexistent@example.com',
        ]);
    }

    /**
     * Test password reset with valid token updates password.
     *
     * Scenario: POST /swift-auth/password/reset with valid token updates password
     */
    public function test_password_reset_with_valid_token_updates_password(): void
    {
        // Arrange
        $user = $this->createTestUser(['email' => 'user@example.com']);
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        PasswordResetToken::create([
            'email' => 'user@example.com',
            'token' => $hashedToken,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/password/reset', [
            'email' => 'user@example.com',
            'token' => $rawToken,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Assert
        $response->assertStatus(200);
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test password reset with invalid token fails.
     *
     * Scenario: POST /swift-auth/password/reset with invalid token returns 400
     */
    public function test_password_reset_with_invalid_token_fails(): void
    {
        // Arrange
        $user = $this->createTestUser(['email' => 'user@example.com']);

        PasswordResetToken::create([
            'email' => 'user@example.com',
            'token' => hash('sha256', 'valid-token'),
            'created_at' => now(),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/password/reset', [
            'email' => 'user@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Assert
        $response->assertStatus(400);
    }

    /**
     * Test password reset with expired token fails.
     *
     * Scenario: POST /swift-auth/password/reset with expired token returns 400
     */
    public function test_password_reset_with_expired_token_fails(): void
    {
        // Arrange
        config(['swift-auth.password_reset_ttl' => 900]); // 15 minutes
        $user = $this->createTestUser(['email' => 'user@example.com']);
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        PasswordResetToken::create([
            'email' => 'user@example.com',
            'token' => $hashedToken,
            'created_at' => now()->subMinutes(20), // Expired
        ]);

        // Act
        $response = $this->postJson('/swift-auth/password/reset', [
            'email' => 'user@example.com',
            'token' => $rawToken,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Assert
        $response->assertStatus(400);
    }

    /**
     * Test password reset deletes token after successful reset.
     *
     * Scenario: POST /swift-auth/password/reset deletes token after use
     */
    public function test_password_reset_deletes_token_after_successful_reset(): void
    {
        // Arrange
        $user = $this->createTestUser(['email' => 'user@example.com']);
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        PasswordResetToken::create([
            'email' => 'user@example.com',
            'token' => $hashedToken,
            'created_at' => now(),
        ]);

        // Act
        $this->postJson('/swift-auth/password/reset', [
            'email' => 'user@example.com',
            'token' => $rawToken,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Assert
        $this->assertDatabaseMissing('swift-auth_PasswordResetTokens', [
            'email' => 'user@example.com',
        ]);
    }

    /**
     * Test password reset validates password confirmation.
     *
     * Scenario: POST /swift-auth/password/reset requires password confirmation
     */
    public function test_password_reset_validates_password_confirmation(): void
    {
        // Arrange
        $user = $this->createTestUser(['email' => 'user@example.com']);
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        PasswordResetToken::create([
            'email' => 'user@example.com',
            'token' => $hashedToken,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/password/reset', [
            'email' => 'user@example.com',
            'token' => $rawToken,
            'password' => 'newpassword123',
            'password_confirmation' => 'different-password',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * Test password reset validates minimum password length.
     *
     * Scenario: POST /swift-auth/password/reset enforces minimum length
     */
    public function test_password_reset_validates_minimum_password_length(): void
    {
        // Arrange
        config(['swift-auth.password_min_length' => 8]);
        $user = $this->createTestUser(['email' => 'user@example.com']);
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        PasswordResetToken::create([
            'email' => 'user@example.com',
            'token' => $hashedToken,
            'created_at' => now(),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/password/reset', [
            'email' => 'user@example.com',
            'token' => $rawToken,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * Test password reset rate limiting.
     *
     * Scenario: POST /swift-auth/password enforces rate limiting
     */
    public function test_password_reset_enforces_rate_limiting(): void
    {
        // Arrange
        config(['swift-auth.password_reset_rate_limit.attempts' => 3]);
        config(['swift-auth.password_reset_rate_limit.decay_seconds' => 60]);
        $user = $this->createTestUser(['email' => 'user@example.com']);
        BirdFlock::fake();

        // Act - Make attempts up to limit
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/swift-auth/password', ['email' => 'user@example.com']);
        }

        // Fourth attempt should be rate limited
        $response = $this->postJson('/swift-auth/password', ['email' => 'user@example.com']);

        // Assert
        $response->assertStatus(429);
    }
}
