<?php

/**
 * Feature tests for multi-factor authentication verification flows.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Feature\Auth
 */

namespace Equidna\SwiftAuth\Tests\Feature\Auth;

use Equidna\SwiftAuth\Tests\TestHelpers;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Validates MFA verification endpoints for OTP and WebAuthn drivers.
 */
class MfaVerificationTest extends TestCase
{
    use RefreshDatabase;
    use TestHelpers;

    public function test_otp_verification_logs_in_user_on_success(): void
    {
        $user = $this->createTestUser();

        config([
            'swift-auth.mfa.otp.verification_url' => 'https://otp.test/verify',
            'swift-auth.mfa.otp.driver' => 'otp-provider',
        ]);

        Http::fake([
            'https://otp.test/verify' => Http::response(['valid' => true], 200),
        ]);

        $response = $this->withSession([
            'swift_auth_pending_user_id' => $user->getKey(),
            'swift_auth_pending_mfa_method' => 'otp',
        ])->postJson('/swift-auth/mfa/otp/verify', [
            'otp' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.user_id', $user->getKey());
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(session()->has('swift_auth_pending_user_id'));
        $this->assertFalse(session()->has('swift_auth_pending_mfa_method'));

        Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://otp.test/verify'
                && $request['otp'] === '123456'
                && $request['driver'] === 'otp-provider'
                && $request['user_id'] === $user->getKey()
                && $request['method'] === 'otp';
        });
    }

    public function test_otp_verification_rejects_invalid_response(): void
    {
        $user = $this->createTestUser();

        config([
            'swift-auth.mfa.otp.verification_url' => 'https://otp.test/verify',
        ]);

        Http::fake([
            'https://otp.test/verify' => Http::response(['valid' => false], 200),
        ]);

        $response = $this->withSession([
            'swift_auth_pending_user_id' => $user->getKey(),
            'swift_auth_pending_mfa_method' => 'otp',
        ])->postJson('/swift-auth/mfa/otp/verify', [
            'otp' => '654321',
        ]);

        $response->assertStatus(401);
        $this->assertGuest();
        $this->assertTrue(session()->has('swift_auth_pending_user_id'));
    }

    public function test_webauthn_verification_logs_in_user_on_success(): void
    {
        $user = $this->createTestUser();

        config([
            'swift-auth.mfa.webauthn.verification_url' => 'https://webauthn.test/verify',
            'swift-auth.mfa.webauthn.driver' => 'webauthn-provider',
        ]);

        Http::fake([
            'https://webauthn.test/verify' => Http::response(['valid' => true], 200),
        ]);

        $response = $this->withSession([
            'swift_auth_pending_user_id' => $user->getKey(),
            'swift_auth_pending_mfa_method' => 'webauthn',
        ])->postJson('/swift-auth/mfa/webauthn/verify', [
            'credential' => [
                'id' => 'credential-id',
                'response' => ['clientDataJSON' => 'payload'],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.user_id', $user->getKey());
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(session()->has('swift_auth_pending_user_id'));
        $this->assertFalse(session()->has('swift_auth_pending_mfa_method'));

        Http::assertSent(function ($request) use ($user) {
            return $request->url() === 'https://webauthn.test/verify'
                && $request['driver'] === 'webauthn-provider'
                && $request['user_id'] === $user->getKey()
                && $request['method'] === 'webauthn'
                && is_array($request['credential']);
        });
    }

    public function test_webauthn_verification_rejects_invalid_response(): void
    {
        $user = $this->createTestUser();

        config([
            'swift-auth.mfa.webauthn.verification_url' => 'https://webauthn.test/verify',
        ]);

        Http::fake([
            'https://webauthn.test/verify' => Http::response(['valid' => false], 200),
        ]);

        $response = $this->withSession([
            'swift_auth_pending_user_id' => $user->getKey(),
            'swift_auth_pending_mfa_method' => 'webauthn',
        ])->postJson('/swift-auth/mfa/webauthn/verify', [
            'credential' => [
                'id' => 'credential-id',
                'response' => ['clientDataJSON' => 'payload'],
            ],
        ]);

        $response->assertStatus(401);
        $this->assertGuest();
        $this->assertTrue(session()->has('swift_auth_pending_user_id'));
    }
}
