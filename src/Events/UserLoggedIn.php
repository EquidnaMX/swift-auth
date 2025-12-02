<?php

namespace Equidna\SwiftAuth\Events;

/**
 * Event fired when a user successfully logs in.
 */
final class UserLoggedIn
{
    /**
     * @param  int|string|null     $userId         Identifier of the authenticated user.
     * @param  string              $sessionId      Session identifier tied to the login.
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
