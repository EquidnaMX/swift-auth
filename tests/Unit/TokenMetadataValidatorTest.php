<?php

namespace Equidna\SwiftAuth\Tests\Unit;

use Equidna\SwiftAuth\Classes\Auth\Services\TokenMetadataValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Equidna\SwiftAuth\Tests\TestCase;

class TokenMetadataValidatorTest extends TestCase
{
    private TokenMetadataValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new TokenMetadataValidator();
    }

    public function test_strict_policy_requires_exact_matches(): void
    {
        config()->set('swift-auth.remember_me', [
            'policy' => 'strict',
            'allow_same_subnet' => false,
            'device_header' => 'X-Device-Id',
            'require_device_header' => true,
        ]);

        $tokenData = [
            'ip' => '192.168.10.5',
            'user_agent' => 'Test UA',
            'device_name' => 'laptop-01',
            'user_id' => 42,
        ];

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '192.168.10.5',
            'HTTP_USER_AGENT' => 'Test UA',
            'HTTP_X_DEVICE_ID' => 'laptop-01',
        ]);

        Log::shouldReceive('warning')->never();

        $this->assertTrue($this->validator->validateWithLogging($tokenData, $request));
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

        $tokenData = [
            'ip' => '10.0.0.10',
            'user_agent' => 'Browser A',
            'device_name' => null,
            'user_id' => 55,
        ];

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '10.0.0.55',
            'HTTP_USER_AGENT' => 'Browser A',
        ]);

        Log::shouldReceive('warning')->never();

        $this->assertTrue($this->validator->validateWithLogging($tokenData, $request));
    }

    public function test_mismatched_metadata_logs_structured_entry(): void
    {
        config()->set('swift-auth.remember_me', [
            'policy' => 'strict',
            'allow_same_subnet' => false,
            'require_device_header' => true,
            'device_header' => 'X-Device-Id',
        ]);

        $tokenData = [
            'ip' => '10.0.0.1',
            'user_agent' => 'Browser A',
            'device_name' => 'laptop-01',
            'user_id' => 99,
        ];

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '10.0.1.5',
            'HTTP_USER_AGENT' => 'Browser B',
            'HTTP_X_DEVICE_ID' => 'tablet-02',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, array $context) {
                return $message === 'swift-auth.remember_me.mismatch'
                    && in_array('ip', $context['mismatched_fields'], true)
                    && in_array('user_agent', $context['mismatched_fields'], true)
                    && in_array('device', $context['mismatched_fields'], true)
                    && $context['token']['user_id'] === 99
                    && $context['request']['ip'] === '10.0.1.5'
                    && $context['token']['ip'] === '10.0.0.1';
            });

        $this->assertFalse($this->validator->validateWithLogging($tokenData, $request));
    }
}
