<?php

namespace Equidna\SwiftAuth\Events;

/**
 * Event fired when a user logs out.
 */
final class UserLoggedOut
{
    /**
     * @param  int|string|null     $userId         Identifier of the user who logged out.
     * @param  string              $sessionId      Session identifier that was cleared.
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
