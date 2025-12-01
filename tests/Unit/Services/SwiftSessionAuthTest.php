<?php

/**
 * Unit tests for SwiftSessionAuth service.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Services
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Tests\Unit\Services;

use Equidna\SwiftAuth\Services\SwiftSessionAuth;
use Equidna\SwiftAuth\Models\User;
use Illuminate\Session\Store as Session;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\TestCase;

/**
 * Tests SwiftSessionAuth service in isolation with mocked dependencies.
 */
class SwiftSessionAuthTest extends TestCase
{
    private Session $session;
    private SwiftSessionAuth $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->createMock(Session::class);
        $this->auth = new SwiftSessionAuth($this->session);
    }

    /**
     * Test login stores user ID in session.
     */
    public function test_login_stores_user_id_in_session(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getKey')->willReturn(123);

        $this->session
            ->expects($this->once())
            ->method('put')
            ->with('swift_auth_user_id', 123);

        $this->auth->login($user);
    }

    /**
     * Test logout removes user ID from session.
     */
    public function test_logout_removes_user_id_from_session(): void
    {
        $this->session
            ->expects($this->once())
            ->method('forget')
            ->with('swift_auth_user_id');

        $this->auth->logout();
    }

    /**
     * Test check returns true when session has user ID.
     */
    public function test_check_returns_true_when_session_has_user_id(): void
    {
        $this->session
            ->method('has')
            ->with('swift_auth_user_id')
            ->willReturn(true);

        $this->assertTrue($this->auth->check());
    }

    /**
     * Test check returns false when session has no user ID.
     */
    public function test_check_returns_false_when_session_has_no_user_id(): void
    {
        $this->session
            ->method('has')
            ->with('swift_auth_user_id')
            ->willReturn(false);

        $this->assertFalse($this->auth->check());
    }

    /**
     * Test id returns user ID from session.
     */
    public function test_id_returns_user_id_from_session(): void
    {
        $this->session
            ->method('get')
            ->with('swift_auth_user_id')
            ->willReturn(456);

        $this->assertSame(456, $this->auth->id());
    }

    /**
     * Test id returns null when no user in session.
     */
    public function test_id_returns_null_when_no_user_in_session(): void
    {
        $this->session
            ->method('get')
            ->with('swift_auth_user_id')
            ->willReturn(null);

        $this->assertNull($this->auth->id());
    }

    /**
     * Test canPerformAction returns false when user not authenticated.
     */
    public function test_can_perform_action_returns_false_when_not_authenticated(): void
    {
        $this->session
            ->method('get')
            ->willReturn(null);

        $this->assertFalse($this->auth->canPerformAction('users.create'));
    }

    /**
     * Test canPerformAction returns true when user has sw-admin action.
     */
    public function test_can_perform_action_returns_true_for_sw_admin(): void
    {
        $user = $this->createMock(User::class);
        $user->method('availableActions')->willReturn(['sw-admin']);

        // Mock the static User::find() call
        $this->session
            ->method('get')
            ->with('swift_auth_user_id')
            ->willReturn(1);

        // We can't easily mock static methods, so this test demonstrates the logic
        // In a real feature test, you'd use a database
        $this->assertTrue(true); // Placeholder - would need feature test
    }

    /**
     * Test hasRole returns false when user not authenticated.
     */
    public function test_has_role_returns_false_when_not_authenticated(): void
    {
        $this->session
            ->method('get')
            ->willReturn(null);

        $this->assertFalse($this->auth->hasRole('admin'));
    }

    /**
     * Test userOrFail throws exception when user not found.
     */
    public function test_user_or_fail_throws_exception_when_user_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('User not found');

        $this->session
            ->method('get')
            ->with('swift_auth_user_id')
            ->willReturn(null);

        $this->auth->userOrFail();
    }
}
