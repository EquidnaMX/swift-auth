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

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\Store as Session;
use SessionHandlerInterface;

use PHPUnit\Framework\TestCase;

use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Services\SwiftSessionAuth;

/**
 * Tests SwiftSessionAuth service in isolation with mocked dependencies.
 */
class SwiftSessionAuthTest extends TestCase
{
    private Session $session;
    private \Equidna\SwiftAuth\Contracts\UserRepositoryInterface $userRepository;
    private Dispatcher $events;
    private SwiftSessionAuth $auth;


    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->createMock(Session::class);
        $this->userRepository = $this->createMock(\Equidna\SwiftAuth\Contracts\UserRepositoryInterface::class);
        $this->events = $this->createMock(Dispatcher::class);
        $this->auth = new SwiftSessionAuth($this->session, $this->userRepository, $this->events);
    }

    /**
     * Tests login stores user ID in session.
     */
    public function test_login_stores_user_id_in_session(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getKey')->willReturn(123);

        $this->session
            ->expects($this->atLeastOnce())
            ->method('put');

        $this->session
            ->method('regenerate')
            ->willReturn(true);

        $this->session
            ->method('getId')
            ->willReturn('sess-123');

        $this->mockDriverMetadata('sess-123');

        $result = $this->auth->login($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('evicted_session_ids', $result);
    }

    /**
     * Tests logout removes user ID from session.
     */
    public function test_logout_removes_user_id_from_session(): void
    {
        $this->session
            ->expects($this->atLeastOnce())
            ->method('forget');

        $this->session
            ->method('get')
            ->willReturn(null);

        $this->session
            ->method('invalidate')
            ->willReturn(true);

        $this->session
            ->method('regenerate')
            ->willReturn(true);

        $this->mockDriverMetadata('sess-456');

        $this->auth->logout();

        $this->assertTrue(true); // If no exception, logout succeeded
    }

    /**
     * Tests check returns true when session has user ID.
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
     * Tests check returns false when session has no user ID.
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
     * Tests id returns user ID from session.
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
     * Tests id returns null when no user in session.
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
     * Tests canPerformAction returns false when user not authenticated.
     */
    public function test_can_perform_action_returns_false_when_not_authenticated(): void
    {
        $this->session
            ->method('get')
            ->willReturn(null);

        $this->assertFalse($this->auth->canPerformAction('users.create'));
    }

    /**
     * Tests canPerformAction returns true when user has sw-admin action.
     */
    public function test_can_perform_action_returns_true_for_sw_admin(): void
    {
        $user = $this->createMock(User::class);
        $user->method('availableActions')->willReturn(['sw-admin']);

        $userRepository = $this->createMock(\Equidna\SwiftAuth\Contracts\UserRepositoryInterface::class);
        $userRepository->method('findById')->with(1)->willReturn($user);

        $events = $this->createMock(Dispatcher::class);

        $this->session
            ->method('get')
            ->with('swift_auth_user_id')
            ->willReturn(1);

        $auth = new SwiftSessionAuth($this->session, $userRepository, $events);

        // User with sw-admin should be able to perform any action
        $this->assertTrue($auth->canPerformAction('create_user'));
        $this->assertTrue($auth->canPerformAction('delete_user'));
        $this->assertTrue($auth->canPerformAction(['manage_roles', 'view_reports']));
    }

    /**
     * Tests hasRole returns false when user not authenticated.
     */
    public function test_has_role_returns_false_when_not_authenticated(): void
    {
        $this->session
            ->method('get')
            ->willReturn(null);

        $this->assertFalse($this->auth->hasRole('admin'));
    }

    /**
     * Tests userOrFail throws exception when user not found.
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

    /**
     * Tests enforceSessionLimit returns evicted session IDs.
     */
    public function test_enforce_session_limit_returns_array(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getKey')->willReturn(55);

        $this->mockDriverMetadata('sess-900');

        $result = $this->auth->enforceSessionLimit($user, 'sess-900');

        $this->assertIsArray($result);
    }

    /**
     * Tests startMfaChallenge stores pending MFA state.
     */
    public function test_start_mfa_challenge_stores_pending_state(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getKey')->willReturn(88);

        $this->session
            ->expects($this->atLeastOnce())
            ->method('put');

        $this->mockDriverMetadata('sess-mfa');

        $this->auth->startMfaChallenge($user, 'otp');

        $this->assertTrue(true); // If no exception, challenge started
    }

    /**
     * Configures session expectations for metadata helpers.
     *
     * @param  string $sessionId
     * @return void
     */
    private function mockDriverMetadata(string $sessionId): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';

        $handler = $this->createMock(SessionHandlerInterface::class);

        $this->session
            ->method('getHandler')
            ->willReturn($handler);

        $this->session
            ->method('getName')
            ->willReturn('laravel_session');

        $this->session
            ->method('getId')
            ->willReturn($sessionId);
    }

    /**
     * Expected driver metadata payload for assertions.
     *
     * @return array<string, string>
     */
    private function expectedDriverMetadata(): array
    {
        return [
            'handler' => SessionHandlerInterface::class,
            'name' => 'laravel_session',
            'store' => Session::class,
        ];
    }
}
