<?php

/**
 * Implements remember-me token validation with device and network awareness.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Services
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Services;

use Equidna\SwiftAuth\DTO\RememberToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Validates remember-me login attempts against stored token metadata.
 */
class RememberMeService
{
    /**
     * Validates an incoming remember-me request against the stored token metadata.
     *
     * @param  Request       $request  Incoming HTTP request.
     * @param  RememberToken $token    Persisted token metadata.
     * @return bool                    True if the request matches the configured policy.
     */
    public function attemptRememberLogin(Request $request, RememberToken $token): bool
    {
        $config = config('swift-auth.remember_me', []);

        $policy = is_string($config['policy'] ?? null)
            ? strtolower((string) $config['policy'])
            : 'strict';

        $allowSubnet = (bool) ($config['allow_same_subnet'] ?? false);
        $subnetMask = (int) ($config['subnet_mask'] ?? 24);
        $deviceHeader = $config['device_header'] ?? 'X-Device-Id';
        $requireDeviceHeader = (bool) ($config['require_device_header'] ?? false);

        $requestIp = (string) ($request->ip() ?? '');
        $requestUserAgent = (string) ($request->userAgent() ?? '');
        $requestDevice = $deviceHeader ? (string) ($request->header($deviceHeader) ?? '') : '';

        $ipMatch = $this->ipMatches($token->ipAddress, $requestIp, $allowSubnet, $subnetMask);
        $userAgentMatch = $this->stringsMatch($token->userAgent, $requestUserAgent);
        $deviceMatch = $this->deviceMatches($token->deviceName, $requestDevice, $requireDeviceHeader);

        $matches = [
            'ip' => $ipMatch,
            'user_agent' => $userAgentMatch,
            'device' => $deviceMatch,
        ];

        $accepted = $policy === 'lenient'
            ? $this->passesLenientPolicy($matches)
            : $this->passesStrictPolicy($matches);

        if (!$accepted) {
            $this->logMismatch(
                policy: $policy,
                requestIp: $requestIp,
                tokenIp: $token->ipAddress,
                requestUserAgent: $requestUserAgent,
                tokenUserAgent: $token->userAgent,
                requestDevice: $requestDevice,
                tokenDevice: $token->deviceName,
                userId: $token->userId,
                matches: $matches,
            );
        }

        return $accepted;
    }

    /**
     * Evaluates strict policy: all attributes must match.
     */
    private function passesStrictPolicy(array $matches): bool
    {
        return $matches['ip'] && $matches['user_agent'] && $matches['device'];
    }

    /**
     * Evaluates lenient policy: IP must match and either user agent or device matches.
     */
    private function passesLenientPolicy(array $matches): bool
    {
        return $matches['ip'] && ($matches['user_agent'] || $matches['device']);
    }

    /**
     * Compares two string values using a case-sensitive comparison.
     */
    private function stringsMatch(?string $expected, ?string $actual): bool
    {
        return isset($expected, $actual) && strcmp($expected, $actual) === 0;
    }

    /**
     * Determines if device headers match or are optional.
     */
    private function deviceMatches(?string $expectedDevice, string $actualDevice, bool $requireDeviceHeader): bool
    {
        if (!$requireDeviceHeader && ($expectedDevice === null || $expectedDevice === '')) {
            return true;
        }

        return $expectedDevice !== null
            && $expectedDevice !== ''
            && $actualDevice !== ''
            && strcmp($expectedDevice, $actualDevice) === 0;
    }

    /**
     * Compares IP addresses with optional subnet tolerance.
     */
    private function ipMatches(?string $expectedIp, string $actualIp, bool $allowSubnet, int $subnetMask): bool
    {
        if (!$expectedIp || !$actualIp) {
            return false;
        }

        if ($expectedIp === $actualIp) {
            return true;
        }

        if (!$allowSubnet) {
            return false;
        }

        return $this->inSameSubnet($expectedIp, $actualIp, $subnetMask);
    }

    /**
     * Determines if two IP addresses share the same subnet using the provided mask.
     */
    private function inSameSubnet(string $ipA, string $ipB, int $mask): bool
    {
        $packedA = @inet_pton($ipA);
        $packedB = @inet_pton($ipB);

        if ($packedA === false || $packedB === false || strlen($packedA) !== strlen($packedB)) {
            return false;
        }

        $bytes = intdiv($mask, 8);
        $remainingBits = $mask % 8;

        if ($bytes > 0 && substr($packedA, 0, $bytes) !== substr($packedB, 0, $bytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $maskByte = chr((0xFF << (8 - $remainingBits)) & 0xFF);

        return (ord($packedA[$bytes]) & ord($maskByte)) === (ord($packedB[$bytes]) & ord($maskByte));
    }

    /**
     * Logs structured mismatch details for auditing.
     */
    private function logMismatch(
        string $policy,
        string $requestIp,
        ?string $tokenIp,
        string $requestUserAgent,
        ?string $tokenUserAgent,
        string $requestDevice,
        ?string $tokenDevice,
        ?int $userId,
        array $matches,
    ): void {
        $mismatched = array_keys(array_filter($matches, fn (bool $match) => !$match));

        Log::warning('swift-auth.remember_me.mismatch', [
            'policy' => $policy,
            'mismatched_fields' => $mismatched,
            'request' => [
                'ip' => $requestIp,
                'user_agent' => $requestUserAgent,
                'device' => $requestDevice,
            ],
            'token' => [
                'ip' => $tokenIp,
                'user_agent' => $tokenUserAgent,
                'device' => $tokenDevice,
                'user_id' => $userId,
            ],
        ]);
    }
}
