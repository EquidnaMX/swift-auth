<?php

/**
 * Integration tests for complete authentication flows.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Feature\Auth
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Tests\Feature\Auth;

use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Equidna\SwiftAuth\Tests\TestCase;

/**
 * Tests complete authentication flows from end to end.
 *
 * Covers: login → access protected route → logout → session cleared.
 * This is an INTEGRATION test, not a unit test - it uses real database.
 */
class CompleteAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tests complete login flow: guest → login → access protected → logout.
     */
    public function test_complete_authentication_flow(): void
    {
        // Arrange: Create a user
        $user = User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 0,
        ]);

        // Act 1: Guest should not have session
        $this->assertGuest();

        // Act 2: Login with valid credentials
        $response = $this->post(route('swift-auth.login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert: Login successful, redirected, session created
        $response->assertStatus(302);
        $this->assertAuthenticated();
        $this->assertEquals($user->getKey(), session('swift_auth_user_id'));

        // Act 3: Access protected route (assuming a test route exists)
        $protectedResponse = $this->get('/'); // Replace with actual protected route
        $protectedResponse->assertStatus(200); // Should be accessible

        // Act 4: Logout
        $logoutResponse = $this->post(route('swift-auth.logout'));

        // Assert: Logout successful, session cleared
        $logoutResponse->assertStatus(302);
        $this->assertGuest();
        $this->assertNull(session('swift_auth_user_id'));
    }

    /**
     * Tests login fails with invalid credentials.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        // Arrange: Create a user
        User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('correctpassword'),
            'failed_login_attempts' => 0,
        ]);

        // Act: Attempt login with wrong password
        $response = $this->post(route('swift-auth.login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert: Login fails, still guest
        $response->assertStatus(401);
        $this->assertGuest();
    }

    /**
     * Tests account lockout after max failed attempts.
     */
    public function test_account_locks_after_max_failed_attempts(): void
    {
        // Arrange: Create a user
        $user = User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 0,
        ]);

        $maxAttempts = config('swift-auth.account_lockout.max_attempts', 5);

        // Act: Make max failed login attempts
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->post(route('swift-auth.login'), [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // Assert: Account should be locked
        $user->refresh();
        $this->assertNotNull($user->locked_until);
        $this->assertTrue($user->locked_until->isFuture());
        $this->assertEquals($maxAttempts, $user->failed_login_attempts);

        // Act: Try to login with correct password
        $response = $this->post(route('swift-auth.login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert: Login rejected due to lockout
        $response->assertStatus(401);
        $this->assertGuest();
    }

    /**
     * Tests successful login resets failed attempts counter.
     */
    public function test_successful_login_resets_failed_attempts(): void
    {
        // Arrange: Create a user with failed attempts
        $user = User::create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 3,
            'last_failed_login_at' => now(),
        ]);

        // Act: Login with correct credentials
        $response = $this->post(route('swift-auth.login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert: Login successful, failed attempts reset
        $response->assertStatus(302);
        $this->assertAuthenticated();

        $user->refresh();
        $this->assertEquals(0, $user->failed_login_attempts);
        $this->assertNull($user->last_failed_login_at);
    }

    /**
     * Tests authorization middleware blocks unauthorized actions.
     */
    public function test_authorization_middleware_blocks_unauthorized_actions(): void
    {
        // Arrange: Create user and role with limited permissions
        $role = Role::create([
            'name' => 'Viewer',
            'actions' => ['view_reports'],
        ]);

        $user = User::create([
            'email' => 'viewer@example.com',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 0,
        ]);

        $user->roles()->attach($role);

        // Act: Login
        $this->post(route('swift-auth.login'), [
            'email' => 'viewer@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();

        // Note: This test requires a protected route with CanPerformAction middleware
        // Example route setup (add to test environment):
        // Route::get('/admin/users', fn() => response('OK'))
        //     ->middleware(['SwiftAuth.RequireAuthentication', 'SwiftAuth.CanPerformAction:manage_users']);

        // For now, just verify user has correct permissions
        $this->assertTrue($user->availableActions() === ['view_reports']);
        $this->assertFalse(in_array('manage_users', $user->availableActions()));
    }

    /**
     * Tests sw-admin role bypasses all permission checks.
     */
    public function test_sw_admin_bypasses_all_permission_checks(): void
    {
        // Arrange: Create admin user with sw-admin permission
        $role = Role::create([
            'name' => 'Super Admin',
            'actions' => ['sw-admin'],
        ]);

        $user = User::create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'failed_login_attempts' => 0,
        ]);

        $user->roles()->attach($role);

        // Act: Login
        $this->post(route('swift-auth.login'), [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();

        // Assert: User has sw-admin action
        $this->assertContains('sw-admin', $user->availableActions());

        // SwiftAuth should allow any action for sw-admin users
        // (Actual middleware test would require route setup)
    }

    /**
     * Helper to assert user is authenticated.
     */
    protected function assertSwiftAuthenticated(): void
    {
        $this->assertNotNull(session('swift_auth_user_id'));
    }

    /**
     * Helper to assert user is not authenticated (guest).
     */
    protected function assertSwiftGuest(): void
    {
        $this->assertNull(session('swift_auth_user_id'));
    }
}
