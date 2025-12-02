<?php

namespace Equidna\SwiftAuth\Events;

/**
 * Event fired when a session is evicted to enforce limits.
 */
final class SessionEvicted
{
    /**
     * @param  int|string|null     $userId         Identifier of the affected user.
     * @param  string              $sessionId      Identifier of the evicted session.
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
