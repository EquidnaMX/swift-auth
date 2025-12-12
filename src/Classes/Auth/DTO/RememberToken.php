<?php

/**
 * Value object representing a persisted remember-me token with metadata.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\DTO
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Classes\Auth\DTO;

/**
 * Immutable token details used for remember-me validation.
 */
class RememberToken
{
    /**
     * @param  string       $token       Raw token value (already persisted).
     * @param  string|null  $ipAddress   IP address recorded when the token was created.
     * @param  string|null  $userAgent   User agent recorded when the token was created.
     * @param  string|null  $deviceName  Optional device identifier/header recorded with the token.
     * @param  int|null     $userId      Optional associated user ID for logging.
     */
    public function __construct(
        public readonly string $token,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $deviceName = null,
        public readonly ?int $userId = null,
    ) {
    }
}
