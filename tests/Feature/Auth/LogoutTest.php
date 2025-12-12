<?php

/**
 * Feature tests for logout flow.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Feature\Auth
 * @author    QA Team
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests\Feature\Auth;

use Equidna\SwiftAuth\Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Equidna\SwiftAuth\Tests\TestCase;

/**
 * Feature tests for logout flow.
 *
 * Reference: NON_UNIT_TEST_REQUESTS.md - Priority 1: Authentication Flow
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;
    use TestHelpers;

    /**
     * Test logout clears session for authenticated user.
     *
     * Scenario: POST /swift-auth/logout clears session
     */
    public function test_logout_clears_session_for_authenticated_user(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->actingAs($user);

        // Act
        $response = $this->postJson('/swift-auth/logout');

        // Assert
        $response->assertStatus(200);
        $this->assertGuest();
    }

    /**
     * Test logout invalidates session.
     *
     * Scenario: POST /swift-auth/logout invalidates session in storage
     */
    public function test_logout_invalidates_session(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->actingAs($user);
        $oldSessionId = session()->getId();

        // Act
        $this->postJson('/swift-auth/logout');

        // Assert
        $newSessionId = session()->getId();
        $this->assertNotEquals($oldSessionId, $newSessionId);
    }

    /**
     * Test logout returns success response.
     *
     * Scenario: POST /swift-auth/logout returns 200 for authenticated user
     */
    public function test_logout_returns_success_response(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->actingAs($user);

        // Act
        $response = $this->postJson('/swift-auth/logout');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Test logout works for unauthenticated users (idempotent).
     *
     * Scenario: POST /swift-auth/logout returns 200 even for unauthenticated user
     */
    public function test_logout_is_idempotent_for_guest_users(): void
    {
        // Act - no authentication
        $response = $this->postJson('/swift-auth/logout');

        // Assert
        $response->assertStatus(200);
        $this->assertGuest();
    }

    /**
     * Test logout logs audit trail.
     *
     * Scenario: POST /swift-auth/logout logs audit trail
     */
    public function test_logout_logs_audit_trail(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->actingAs($user);

        Log::spy();

        // Act
        $this->postJson('/swift-auth/logout');

        // Assert
        Log::shouldHaveReceived('info')
            ->once()
            ->with('User logged out', \Mockery::type('array'));
    }

    /**
     * Test logout regenerates CSRF token.
     *
     * Scenario: POST /swift-auth/logout regenerates CSRF token
     */
    public function test_logout_regenerates_csrf_token(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->actingAs($user);

        $oldToken = csrf_token();

        // Act
        $this->postJson('/swift-auth/logout');

        // Assert
        $newToken = csrf_token();
        $this->assertNotEquals($oldToken, $newToken);
    }

    /**
     * Test GET logout also works (convenience).
     *
     * Scenario: GET /swift-auth/logout works for convenience
     */
    public function test_logout_works_via_get_request(): void
    {
        // Arrange
        $user = $this->createTestUser();
        $this->actingAs($user);

        // Act
        $response = $this->getJson('/swift-auth/logout');

        // Assert
        $response->assertStatus(200);
        $this->assertGuest();
    }
}
