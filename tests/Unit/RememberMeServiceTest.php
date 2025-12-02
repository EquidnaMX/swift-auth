<?php

/**
 * Unit tests for remember-me metadata validation.
 */

namespace Equidna\SwiftAuth\Tests\Unit;

use Equidna\SwiftAuth\DTO\RememberToken;
use Equidna\SwiftAuth\Services\RememberMeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class RememberMeServiceTest extends TestCase
{
    /** @var RememberMeService */
    private RememberMeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RememberMeService();
        Log::fake();
    }

    public function test_strict_policy_requires_exact_matches(): void
    {
        config()->set('swift-auth.remember_me', [
            'policy' => 'strict',
            'allow_same_subnet' => false,
            'device_header' => 'X-Device-Id',
            'require_device_header' => true,
        ]);

        $token = new RememberToken(
            token: 'abc',
            ipAddress: '192.168.10.5',
            userAgent: 'Test UA',
            deviceName: 'laptop-01',
            userId: 42,
        );

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '192.168.10.5',
            'HTTP_USER_AGENT' => 'Test UA',
            'HTTP_X_DEVICE_ID' => 'laptop-01',
        ]);

        $this->assertTrue($this->service->attemptRememberLogin($request, $token));
        Log::assertNothingLogged();
    }

    public function test_subnet_match_is_allowed_in_lenient_policy(): void
    {
        config()->set('swift-auth.remember_me', [
            'policy' => 'lenient',
            'allow_same_subnet' => true,
            'subnet_mask' => 24,
            'require_device_header' => false,
            'device_header' => 'X-Device-Id',
        ]);

        $token = new RememberToken(
            token: 'abc',
            ipAddress: '10.0.0.10',
            userAgent: 'Browser A',
            deviceName: null,
        );

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '10.0.0.55',
            'HTTP_USER_AGENT' => 'Browser A',
        ]);

        $this->assertTrue($this->service->attemptRememberLogin($request, $token));
        Log::assertNothingLogged();
    }

    public function test_mismatched_metadata_logs_structured_entry(): void
    {
        config()->set('swift-auth.remember_me', [
            'policy' => 'strict',
            'allow_same_subnet' => false,
            'require_device_header' => true,
            'device_header' => 'X-Device-Id',
        ]);

        $token = new RememberToken(
            token: 'abc',
            ipAddress: '10.0.0.1',
            userAgent: 'Browser A',
            deviceName: 'laptop-01',
            userId: 99,
        );

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '10.0.1.5',
            'HTTP_USER_AGENT' => 'Browser B',
            'HTTP_X_DEVICE_ID' => 'tablet-02',
        ]);

        $this->assertFalse($this->service->attemptRememberLogin($request, $token));

        Log::assertLogged('warning', function ($message, array $context) {
            return $message === 'swift-auth.remember_me.mismatch'
                && in_array('ip', $context['mismatched_fields'], true)
                && in_array('user_agent', $context['mismatched_fields'], true)
                && in_array('device', $context['mismatched_fields'], true)
                && $context['token']['user_id'] === 99
                && $context['request']['ip'] === '10.0.1.5'
                && $context['token']['ip'] === '10.0.0.1';
        });
    }
}
