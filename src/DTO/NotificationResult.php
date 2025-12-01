<?php

/**
 * Value object representing the outcome of a notification dispatch.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\DTO
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\DTO;

/**
 * Represents the result of a Bird Flock notification dispatch.
 *
 * Encapsulates success status, optional message ID, and error details for resilient
 * notification handling.
 */
final readonly class NotificationResult
{
    /**
     * Creates a notification result instance.
     *
     * @param bool        $success    Whether the dispatch succeeded.
     * @param string|null $messageId  Bird Flock message ID on success.
     * @param string|null $error      Error message on failure.
     */
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public ?string $error = null,
    ) {
        //
    }

    /**
     * Creates a successful result with a message ID.
     *
     * @param  string              $messageId  Bird Flock message identifier.
     * @return NotificationResult              Success result instance.
     */
    public static function success(string $messageId): self
    {
        return new self(
            success: true,
            messageId: $messageId,
        );
    }

    /**
     * Creates a failed result with an error message.
     *
     * @param  string              $error  Human-readable error description.
     * @return NotificationResult          Failure result instance.
     */
    public static function failure(string $error): self
    {
        return new self(
            success: false,
            error: $error,
        );
    }
}
