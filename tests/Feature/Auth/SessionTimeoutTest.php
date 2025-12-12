<?php

namespace Equidna\SwiftAuth\Tests\Feature\Auth;

use Carbon\CarbonImmutable;
use Equidna\SwiftAuth\Facades\SwiftAuth;
use Equidna\SwiftAuth\Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Equidna\SwiftAuth\Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;
    use TestHelpers;

    protected function tearDown(): void
    {
        parent::tearDown();

        CarbonImmutable::setTestNow();
        Cookie::flushQueuedCookies();
    }

    public function test_session_expires_after_idle_timeout(): void
    {
        config(['swift-auth.session.idle_timeout' => 300]);
        config(['swift-auth.session.absolute_timeout' => null]);

        $now = CarbonImmutable::create(2024, 1, 1, 12);
        CarbonImmutable::setTestNow($now);

        $user = $this->createTestUser();

        SwiftAuth::login($user);
        $this->assertTrue(SwiftAuth::check());

        CarbonImmutable::setTestNow($now->addSeconds(301));

        $this->assertFalse(SwiftAuth::check());
        $this->assertFalse(session()->has('swift_auth_user_id'));
    }

    public function test_session_expires_after_absolute_timeout(): void
    {
        config(['swift-auth.session.idle_timeout' => null]);
        config(['swift-auth.session.absolute_timeout' => 600]);

        $now = CarbonImmutable::create(2024, 1, 1, 12);
        CarbonImmutable::setTestNow($now);

        $user = $this->createTestUser();

        SwiftAuth::login($user);
        $this->assertTrue(SwiftAuth::check());

        CarbonImmutable::setTestNow($now->addSeconds(601));

        $this->assertFalse(SwiftAuth::check());
        $this->assertFalse(session()->has('swift_auth_user_id'));
    }

    public function test_remember_me_reauthentication_rotates_token(): void
    {
        config(['swift-auth.session.idle_timeout' => null]);
        config(['swift-auth.session.absolute_timeout' => null]);
        config(['swift-auth.session.remember_me.rotate' => true]);

        $now = CarbonImmutable::create(2024, 1, 1, 12);
        CarbonImmutable::setTestNow($now);

        $user = $this->createTestUser();
        $initialToken = Str::random(40);
        $user->remember_token = hash('sha256', $initialToken);
        $user->save();

        $cookieValue = implode('|', [
            $user->getKey(),
            $initialToken,
            $now->addHours()->getTimestamp(),
        ]);

        session()->flush();
        $this->app['request']->cookies->set('swift_auth_remember', $cookieValue);

        $this->assertTrue(SwiftAuth::check());

        $rotatedCookie = collect(Cookie::getQueuedCookies())
            ->first(fn($cookie) => $cookie->getName() === 'swift_auth_remember');

        $this->assertNotNull($rotatedCookie);
        $this->assertNotSame($cookieValue, $rotatedCookie->getValue());
        $this->assertNotSame(
            $user->fresh()->remember_token,
            hash('sha256', $initialToken),
        );
    }

    public function test_remember_me_reauthentication_without_rotation(): void
    {
        config(['swift-auth.session.idle_timeout' => null]);
        config(['swift-auth.session.absolute_timeout' => null]);
        config(['swift-auth.session.remember_me.rotate' => false]);

        $now = CarbonImmutable::create(2024, 1, 1, 12);
        CarbonImmutable::setTestNow($now);

        $user = $this->createTestUser();
        $initialToken = Str::random(40);
        $hashedToken = hash('sha256', $initialToken);
        $user->remember_token = $hashedToken;
        $user->save();

        $cookieValue = implode('|', [
            $user->getKey(),
            $initialToken,
            $now->addHours()->getTimestamp(),
        ]);

        session()->flush();
        $this->app['request']->cookies->set('swift_auth_remember', $cookieValue);

        $this->assertTrue(SwiftAuth::check());

        $queuedCookie = collect(Cookie::getQueuedCookies())
            ->first(fn($cookie) => $cookie->getName() === 'swift_auth_remember');

        if ($queuedCookie) {
            $this->assertSame($cookieValue, $queuedCookie->getValue());
        }

        $this->assertSame($hashedToken, $user->fresh()->remember_token);
    }

    public function test_expired_remember_token_is_purged_and_cookie_cleared(): void
    {
        config(['swift-auth.session.remember_me.rotate' => true]);

        $now = CarbonImmutable::create(2024, 1, 1, 12);
        CarbonImmutable::setTestNow($now);

        $user = $this->createTestUser();
        $initialToken = Str::random(40);
        $user->remember_token = hash('sha256', $initialToken);
        $user->save();

        $cookieValue = implode('|', [
            $user->getKey(),
            $initialToken,
            $now->subMinute()->getTimestamp(),
        ]);

        session()->flush();
        $this->app['request']->cookies->set('swift_auth_remember', $cookieValue);

        $this->assertFalse(SwiftAuth::check());

        $this->assertNull($user->fresh()->remember_token);

        $clearedCookie = collect(Cookie::getQueuedCookies())
            ->first(fn($cookie) => $cookie->getName() === 'swift_auth_remember');

        $this->assertNotNull($clearedCookie);
        $this->assertEmpty($clearedCookie->getValue());
    }
}
