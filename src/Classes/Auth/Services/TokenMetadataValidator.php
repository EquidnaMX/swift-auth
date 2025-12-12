<?php

namespace Equidna\SwiftAuth\Classes\Auth\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TokenMetadataValidator
{
    public function validate(
        string $tokenIp,
        string $tokenUserAgent,
        ?string $tokenDeviceName,
        Request $request
    ): bool {
        $policy = config('swift-auth.remember_me.policy', 'strict');
        $mismatches = [];

        // IP Check
        if (!$this->checkIp($policy, $tokenIp, $request->ip())) {
            $mismatches[] = 'ip';
        }

        // User Agent Check
        if ($tokenUserAgent !== $request->userAgent()) {
            $mismatches[] = 'user_agent';
        }

        // Device Header Check
        $requireDevice = config('swift-auth.remember_me.require_device_header', false);
        $deviceHeader = config('swift-auth.remember_me.device_header', 'X-Device-Id');

        if ($requireDevice || $tokenDeviceName !== null) {
            $requestDevice = $request->header($deviceHeader);
            if ($tokenDeviceName !== $requestDevice) {
                // If strictly required OR token had it recorded but request doesn't match
                $mismatches[] = 'device';
            }
        }

        // If 'lenient', we might ignore some mismatches, but the test implies strict mostly.
        // Actually the test `test_subnet_match_is_allowed_in_lenient_policy` implies IP check varies.
        // But UA/Device seem consistent.

        if (empty($mismatches)) {
            return true;
        }

        // Logging handled by caller or here? The test expects logging.
        // The original test expected `RememberMeService` to log.
        // Better to return the result and mismatches, or log here.
        // I'll log here to satisfy the test expectation which spies on Log.

        // Construct context for logging
        // But wait, I need user_id for logging context to match the test.
        // Validate signature probably needs userId and tokenIp too.

        // Let's change signature to accept more context if we want to log here.
        return count($mismatches) === 0;
    }

    public function validateWithLogging(
        array $tokenData, // ['ip' => ..., 'user_agent' => ..., 'device_name' => ..., 'user_id' => ...]
        Request $request
    ): bool {
        $policy = config('swift-auth.remember_me.policy', 'strict');
        $mismatches = [];

        $tokenIp = $tokenData['ip'];
        $tokenUserAgent = $tokenData['user_agent'];
        $tokenDeviceName = $tokenData['device_name'] ?? null;

        // IP Check
        if (!$this->checkIp($policy, $tokenIp, $request->ip())) {
            $mismatches[] = 'ip';
        }

        // User Agent Check
        if ($tokenUserAgent !== $request->userAgent()) {
            $mismatches[] = 'user_agent';
        }

        // Device Header Check
        $requireDevice = config('swift-auth.remember_me.require_device_header', false);
        $deviceHeader = config('swift-auth.remember_me.device_header', 'X-Device-Id');
        $requestDevice = $request->header($deviceHeader);

        // If required, it must be present and match.
        // If not required but token has it, it must match.
        // If not required and token doesn't have it, ignore?

        // Test `test_strict_policy_requires_exact_matches`: token has device, request has device -> match.
        // Test `test_mismatched_metadata...`: token has device, request has DIFFERENT device -> mismatch.

        if ($requireDevice || $tokenDeviceName !== null) {
            if ($tokenDeviceName !== $requestDevice) {
                $mismatches[] = 'device';
            }
        }

        if (empty($mismatches)) {
            return true;
        }

        logger()->warning('swift-auth.remember_me.mismatch', [
            'mismatched_fields' => $mismatches,
            'token' => [
                'user_id' => $tokenData['user_id'] ?? null,
                'ip' => $tokenIp,
            ],
            'request' => [
                'ip' => $request->ip(),
            ]
        ]);

        return false;
    }

    protected function checkIp(string $policy, string $tokenIp, ?string $requestIp): bool
    {
        if ($tokenIp === $requestIp) {
            return true;
        }

        if ($policy === 'lenient' && config('swift-auth.remember_me.allow_same_subnet', true)) {
            $subnet = config('swift-auth.remember_me.subnet_mask', 24);
            // Simple subnet matching logic
            return $this->cidrMatch($requestIp, $tokenIp, $subnet);
        }

        return false;
    }

    protected function cidrMatch($ip, $target, $mask): bool
    {
        // Simple implementation or use a library if available.
        // user's IP vs token IP.

        // This is a rough check.
        if (!$ip || !$target) {
            return false;
        }

        $ipLong = ip2long($ip);
        $targetLong = ip2long($target);

        if ($ipLong === false || $targetLong === false) {
            return false;
        }

        $maskLong = -1 << (32 - $mask);

        return ($ipLong & $maskLong) === ($targetLong & $maskLong);
    }
}
