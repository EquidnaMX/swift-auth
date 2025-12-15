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

use Equidna\SwiftAuth\Tests\TestCase;

use Equidna\SwiftAuth\Classes\Auth\SwiftSessionAuth;
use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Classes\Auth\Services\RememberMeService;
use Equidna\SwiftAuth\Classes\Auth\Services\SessionManager;
use Equidna\SwiftAuth\Classes\Auth\Services\MfaService;

use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests SwiftSessionAuth service in isolation with mocked dependencies.
 */
class SwiftSessionAuthTest extends TestCase
{
    private Session&MockObject $session;
    private \Equidna\SwiftAuth\Classes\Users\Contracts\UserRepositoryInterface&MockObject $userRepository;
    private Dispatcher&MockObject $events;
    private RememberMeService&MockObject $rememberMeService;
    private SessionManager&MockObject $sessionManager;
    private MfaService&MockObject $mfaService;
    private SwiftSessionAuth $auth;


    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->createMock(Session::class);
        $this->userRepository = $this->createMock(\Equidna\SwiftAuth\Classes\Users\Contracts\UserRepositoryInterface::class);
        $this->events = $this->createMock(Dispatcher::class);
        $this->rememberMeService = $this->createMock(RememberMeService::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->mfaService = $this->createMock(MfaService::class);

        $this->auth = new SwiftSessionAuth(
            $this->session,
            $this->userRepository,
            $this->events,
            $this->rememberMeService,
            $this->sessionManager,
            $this->mfaService
        );
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

        $this->sessionManager
            ->expects($this->once())
            ->method('record')
            ->with(
                $this->equalTo($user),
                'sess-123',
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );

        $this->sessionManager
            ->method('enforceLimits')
            ->willReturn([]);

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
            ->willReturnMap([
                ['swift_auth_session_id', null, 'sess-456'],
                ['swift_auth_user_id', null, 123],
            ]);

        $this->session
            ->method('invalidate')
            ->willReturn(true);

        $this->session
            ->method('regenerate')
            ->willReturn(true);

        $this->mockDriverMetadata('sess-456');

        $this->mfaService->expects($this->once())->method('clearPendingChallenge');

        // SwiftSessionAuth calls session->getId() to check if empty, session mock returns 'sess-456'
        // So it should call deleteById
        $this->sessionManager->expects($this->once())->method('deleteById')->with('sess-456');

        $this->auth->logout();

        $this->assertTrue(true); // If no exception, logout succeeded
    }

    /**
     * Tests check returns true when session has user ID.
     */
    public function test_check_returns_true_when_session_has_user_id(): void
    {
        $this->session
            ->method('get')
            ->willReturnMap([
                ['swift_auth_user_id', null, 123],
                ['swift_auth_session_id', null, 'sess-valid'],
            ]);

        $this->sessionManager
            ->method('isValid')
            ->with('sess-valid')
            ->willReturn(true);

        $user = $this->createMock(User::class);
        $this->userRepository->method('findById')->with(123)->willReturn($user);

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
        $user->method('hasRole')->with('sw-admin')->willReturn(true);

        // Mock check() success flow
        $this->session
            ->method('get')
            ->willReturnMap([
                ['swift_auth_user_id', null, 1],
                ['swift_auth_session_id', null, 'sess-admin'],
            ]);

        $this->sessionManager->method('isValid')->willReturn(true);
        $this->userRepository->method('findById')->with(1)->willReturn($user);

        // CanPerformAction checks user(), which calls check()
        // We only test logic assuming user is retrieved

        $auth = new SwiftSessionAuth(
            $this->session,
            $this->userRepository,
            $this->events,
            $this->rememberMeService,
            $this->sessionManager,
            $this->mfaService
        );

        // We need to re-mock dependencies if we create a new instance, or use $this->auth
        // But the test creates a NEW instance. We should fix that to use $this->auth and set mocks up.
        // Actually, let's just use the properties we already have.

        // Re-setup mocks for this specific test case on $this->auth dependencies
        $this->userRepository = $this->createMock(\Equidna\SwiftAuth\Classes\Users\Contracts\UserRepositoryInterface::class);
        $this->userRepository->method('findById')->with(1)->willReturn($user);

        // Re-instantiate $this->auth with new repo mock
        $this->auth = new SwiftSessionAuth(
            $this->session,
            $this->userRepository,
            $this->events,
            $this->rememberMeService,
            $this->sessionManager,
            $this->mfaService
        );

        // User with sw-admin should be able to perform any action
        $this->assertTrue($this->auth->canPerformAction('create_user'));
        $this->assertTrue($this->auth->canPerformAction('delete_user'));
        $this->assertTrue($this->auth->canPerformAction(['manage_roles', 'view_reports']));
    }

    public function test_enforce_session_limit_returns_array(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getKey')->willReturn(55);

        $this->mockDriverMetadata('sess-900');

        $this->sessionManager
            ->expects($this->once())
            ->method('enforceLimits')
            ->with($user, 'sess-900')
            ->willReturn(['sess-old-1']);

        $result = $this->auth->enforceSessionLimit($user, 'sess-900');

        $this->assertIsArray($result);
        $this->assertContains('sess-old-1', $result);
    }

    public function test_start_mfa_challenge_stores_pending_state(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getKey')->willReturn(88);

        $this->mockDriverMetadata('sess-mfa');

        $this->mfaService
            ->expects($this->once())
            ->method('startChallenge')
            ->with($user, 'otp', '203.0.113.10');

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
        if (property_exists($this, 'app') && $this->app) {
            $this->app['request']->server->set('REMOTE_ADDR', '203.0.113.10');
        } else {
            $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        }

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
