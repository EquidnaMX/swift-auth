<?php

/**
 * Example feature test for authentication flow.
 *
 * This is a TEMPLATE for QA team to use when implementing feature tests.
 * Copy this file and adapt it to test specific scenarios from NON_UNIT_TEST_REQUESTS.md
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Feature\Auth
 * @author    QA Team
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests\Feature\Auth;

use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for login authentication flow.
 *
 * Reference: NON_UNIT_TEST_REQUESTS.md - Priority 1: Authentication Flow
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;
    use TestHelpers;

    /**
     * Test successful login with valid credentials.
     *
     * Scenario: POST /swift-auth/login with valid credentials returns 200 and session
     */
    public function test_login_with_valid_credentials_returns_success(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'user' => ['id_user', 'name', 'email'],
            ],
        ]);

        // Verify session was created
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test login with invalid credentials returns 401.
     *
     * Scenario: POST /swift-auth/login with invalid credentials returns 401
     */
    public function test_login_with_invalid_credentials_returns_unauthorized(): void
    {
        // Arrange
        $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $response->assertStatus(401);
        $this->assertGuest();
    }

    /**
     * Test login regenerates session ID on success.
     *
     * Scenario: POST /swift-auth/login regenerates session ID on successful login
     */
    public function test_login_regenerates_session_id(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Capture initial session ID
        $this->session(['_token' => 'old-token']);
        $oldSessionId = session()->getId();

        // Act
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200);
        $newSessionId = session()->getId();
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    /**
     * Test login resets failed login attempts counter.
     *
     * Scenario: POST /swift-auth/login resets failed_login_attempts on success
     */
    public function test_login_resets_failed_attempts_counter(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 3,
        ]);

        // Act
        $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertEquals(0, $user->fresh()->failed_login_attempts);
    }

    /**
     * Test login increments failed attempts counter on failure.
     *
     * Scenario: POST /swift-auth/login increments failed_login_attempts on failure
     */
    public function test_login_increments_failed_attempts_on_invalid_credentials(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $this->assertEquals(1, $user->fresh()->failed_login_attempts);
    }

    /**
     * Test login locks account after max failed attempts.
     *
     * Scenario: POST /swift-auth/login locks account after 5 failed attempts (configurable)
     */
    public function test_login_locks_account_after_max_failed_attempts(): void
    {
        // Arrange
        config(['swift-auth.account_lockout.max_attempts' => 3]);
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 2, // One away from lockout
        ]);

        // Act
        $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        // Assert
        $user = $user->fresh();
        $this->assertUserIsLocked($user);
        $this->assertEquals(3, $user->failed_login_attempts);
    }

    /**
     * Test login rejects locked account.
     *
     * Scenario: POST /swift-auth/login rejects login when account is locked
     */
    public function test_login_rejects_locked_account(): void
    {
        // Arrange
        $user = $this->createLockedUser(15);

        // Act
        $response = $this->postJson('/swift-auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(423); // Locked
        $response->assertJson([
            'status' => 'error',
        ]);
        $this->assertGuest();
    }

    /**
     * Test login validates CSRF token.
     *
     * Scenario: POST /swift-auth/login validates CSRF token
     */
    public function test_login_validates_csrf_token(): void
    {
        // Arrange
        $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act - without CSRF token
        $response = $this->post('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(419); // CSRF token mismatch
    }

    /**
     * Test login handles missing email field.
     *
     * Scenario: POST /swift-auth/login handles missing email field
     */
    public function test_login_validates_required_email(): void
    {
        // Act
        $response = $this->postJson('/swift-auth/login', [
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login handles missing password field.
     *
     * Scenario: POST /swift-auth/login handles missing password field
     */
    public function test_login_validates_required_password(): void
    {
        // Act
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * Test login trims whitespace from email.
     *
     * Scenario: POST /swift-auth/login trims whitespace from email
     */
    public function test_login_trims_email_whitespace(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/login', [
            'email' => '  user@example.com  ',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test login is case-insensitive for email.
     *
     * Scenario: POST /swift-auth/login is case-insensitive for email
     */
    public function test_login_is_case_insensitive_for_email(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson('/swift-auth/login', [
            'email' => 'USER@EXAMPLE.COM',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test login logs audit trail on success.
     *
     * Scenario: POST /swift-auth/login logs audit trail on success
     */
    public function test_login_logs_successful_attempt(): void
    {
        // Arrange
        $user = $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Expect log entry
        \Log::shouldReceive('info')
            ->once()
            ->with('swift-auth.login.success', \Mockery::type('array'));

        // Act
        $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);
    }

    /**
     * Test login logs security event on failure.
     *
     * Scenario: POST /swift-auth/login logs security event on failure
     */
    public function test_login_logs_failed_attempt(): void
    {
        // Arrange
        $this->createTestUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Expect log entry
        \Log::shouldReceive('warning')
            ->once()
            ->with('swift-auth.login.failed', \Mockery::type('array'));

        // Act
        $this->postJson('/swift-auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);
    }
}
