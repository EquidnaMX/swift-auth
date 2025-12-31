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
    public function __construct(
        public readonly string $token,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?string $deviceName = null,
        public readonly ?int $userId = null,
    ) {
    }
}
