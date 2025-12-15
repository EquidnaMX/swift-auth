<?php

/**
 * Value object representing the outcome of a notification dispatch.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Classes\Notifications\DTO
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Classes\Notifications\DTO;

/**
 * Represents the result of a Bird Flock notification dispatch.
 *
 * Encapsulates success status, optional message ID, and error details for resilient
 * notification handling.
 */
final readonly class NotificationResult
{
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $error = null,
    ) {
        //
    }

    public static function success(string $messageId): self
    {
        return new self(
            success: true,
            messageId: $messageId,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }
}
