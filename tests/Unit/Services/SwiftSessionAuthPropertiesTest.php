<?php

/**
 * Unit tests for SwiftSessionAuth missing properties fix.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Services
 * @author    QA Team
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests\Unit\Services;

use Equidna\SwiftAuth\Classes\Auth\SwiftSessionAuth;
use Equidna\SwiftAuth\Classes\Auth\Services\MfaService;
use Equidna\SwiftAuth\Classes\Auth\Services\RememberMeService;
use Equidna\SwiftAuth\Classes\Auth\Services\SessionManager;
use Equidna\SwiftAuth\Classes\Users\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Models\User;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Session\Store as Session;
use Equidna\SwiftAuth\Tests\TestCase;
use SessionHandlerInterface;

/**
 * Tests that SwiftSessionAuth properly defines and uses new properties.
 */
class SwiftSessionAuthPropertiesTest extends TestCase
{
    private SwiftSessionAuth $auth;
    private Session $session;
    private UserRepositoryInterface $userRepository;
    private Dispatcher $events;
    private RememberMeService $rememberMeService;
    private SessionManager $sessionManager;
    private MfaService $mfaService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->session = $this->createMock(Session::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
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
     * Test that login clears MFA pending keys.
     */
    public function test_login_clears_mfa_pending_keys(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getKey')->willReturn(1);

        // Expect forget to be called for both MFA keys
        $forgetCalls = [];
        $this->session
            ->method('forget')
            ->willReturnCallback(function ($key) use (&$forgetCalls) {
                $forgetCalls[] = $key;
            });

        $this->session->method('regenerate')->willReturn(true);
        $this->session->method('getId')->willReturn('test-session');
        $this->session->method('put');

        $this->mockDriverMetadata();

        $this->sessionManager->method('record');
        $this->sessionManager->method('enforceLimits')->willReturn([]);

        $this->auth->login($user);

        $this->assertContains('swift_auth_pending_mfa_user_id', $forgetCalls);
        $this->assertContains('swift_auth_pending_mfa_driver', $forgetCalls);
    }

    /**
     * Test that logout uses rememberCookieName property.
     */
    public function test_logout_uses_remember_cookie_name(): void
    {
        $this->session->method('get')->willReturn(null);
        $this->session->method('forget');
        $this->session->method('invalidate')->willReturn(true);
        $this->session->method('regenerate')->willReturn(true);

        $this->mfaService->method('clearPendingChallenge');
        $this->sessionManager->method('deleteById');
        $this->rememberMeService->method('deleteToken');
        $this->rememberMeService->method('forgetCookie');
        $this->mockDriverMetadata();

        // Mock Cookie facade
        $this->app->singleton('cookie', function () {
            return new class {
                public function get($key)
                {
                    return null;
                }
            };
        });

        // The implementation references Cookie::get($this->rememberCookieName)
        // which should use 'swift_auth_remember' by default
        $this->auth->logout();

        $this->assertTrue(true, 'Logout completed without errors referencing undefined property');
    }

    /**
     * Test that MFA pending keys are properly defined.
     */
    public function test_mfa_keys_are_defined(): void
    {
        $reflection = new \ReflectionClass(SwiftSessionAuth::class);

        $this->assertTrue(
            $reflection->hasProperty('pendingMfaUserKey'),
            'SwiftSessionAuth should have pendingMfaUserKey property'
        );

        $this->assertTrue(
            $reflection->hasProperty('pendingMfaDriverKey'),
            'SwiftSessionAuth should have pendingMfaDriverKey property'
        );
    }

    /**
     * Test that remember cookie name property is defined.
     */
    public function test_remember_cookie_name_is_defined(): void
    {
        $reflection = new \ReflectionClass(SwiftSessionAuth::class);

        $this->assertTrue(
            $reflection->hasProperty('rememberCookieName'),
            'SwiftSessionAuth should have rememberCookieName property'
        );
    }

    private function mockDriverMetadata(): void
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $handler = $this->createMock(SessionHandlerInterface::class);

        $this->session->method('getHandler')->willReturn($handler);
        $this->session->method('getName')->willReturn('laravel_session');
    }
}
