<?php

namespace Equidna\SwiftAuth\Events;

/**
 * Event fired when a multi-factor authentication challenge is started.
 */
final class MfaChallengeStarted
{
    /**
     * @param  int|string|null     $userId         Identifier of the user undergoing MFA.
     * @param  string              $sessionId      Session identifier tied to the challenge.
     * @param  string|null         $ipAddress      IP address of the client.
     * @param  array<string,mixed> $driverMetadata Metadata describing the session driver/handler.
     */
    public function __construct(
        public readonly int|string|null $userId,
        public readonly string $sessionId,
        public readonly ?string $ipAddress,
        public readonly array $driverMetadata
    ) {
    }
}
