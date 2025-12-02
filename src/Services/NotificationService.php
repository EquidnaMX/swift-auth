<?php

/**
 * Service for sending email notifications via Bird Flock.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Services
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Services;

use Equidna\BirdFlock\BirdFlock;
use Equidna\BirdFlock\DTO\FlightPlan;
use Equidna\SwiftAuth\DTO\NotificationResult;

use RuntimeException;

/**
 * Handles email notifications using Bird Flock messaging bus.
 */
final class NotificationService
{
    /**
     * Sends password reset email.
     *
     * @param  string              $email  Recipient email address.
     * @param  string              $token  Password reset token.
     * @return NotificationResult          Dispatch result with success status and message ID or error.
     */
    public function sendPasswordReset(
        string $email,
        string $token,
    ): NotificationResult
    {
        try {
            $routeNamePrefix = config('swift-auth.route_prefix', 'swift-auth');

            $resetUrl = route(
                $routeNamePrefix . '.password.reset.form',
                parameters: [
                    'token' => $token,
                    'email' => $email,
                ],
                absolute: true,
            );

            $flight = new FlightPlan(
                channel: 'email',
                to: $email,
                subject: 'Password Reset Request',
                html: $this->getPasswordResetHtml($resetUrl, $email),
                text: $this->getPasswordResetText($resetUrl),
                idempotencyKey: "swift-auth:password-reset:{$email}:{$token}",
            );

            $messageId = BirdFlock::dispatch($flight);

            return NotificationResult::success($messageId);
        } catch (RuntimeException $e) {
            logger()->error(
                'swift-auth.notification.password-reset-failed',
                [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            );

            return NotificationResult::failure($e->getMessage());
        }
    }

    /**
     * Sends email verification email.
     *
     * @param  string              $email  Recipient email address.
     * @param  string              $token  Email verification token.
     * @return NotificationResult          Dispatch result with success status and message ID or error.
     */
    public function sendEmailVerification(
        string $email,
        string $token,
    ): NotificationResult
    {
        try {
            $routeNamePrefix = config('swift-auth.route_prefix', 'swift-auth');

            $verifyUrl = route(
                $routeNamePrefix . '.email.verify',
                parameters: [
                    'token' => $token,
                    'email' => $email,
                ],
                absolute: true,
            );

            $flight = new FlightPlan(
                channel: 'email',
                to: $email,
                subject: 'Verify Your Email Address',
                html: $this->getEmailVerificationHtml($verifyUrl, $email),
                text: $this->getEmailVerificationText($verifyUrl),
                idempotencyKey: "swift-auth:email-verification:{$email}:{$token}",
            );

            $messageId = BirdFlock::dispatch($flight);

            return NotificationResult::success($messageId);
        } catch (RuntimeException $e) {
            logger()->error(
                'swift-auth.notification.email-verification-failed',
                [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            );

            return NotificationResult::failure($e->getMessage());
        }
    }

    /**
     * Sends account lockout notification.
     *
     * @param  string              $email     Recipient email address.
     * @param  int                 $duration  Lockout duration in seconds.
     * @return NotificationResult             Dispatch result with success status and message ID or error.
     */
    public function sendAccountLockout(
        string $email,
        int $duration,
    ): NotificationResult
    {
        try {
            $minutes = (int) ceil($duration / 60);

            $flight = new FlightPlan(
                channel: 'email',
                to: $email,
                subject: 'Account Temporarily Locked',
                html: $this->getAccountLockoutHtml($email, $minutes),
                text: $this->getAccountLockoutText($minutes),
                idempotencyKey: "swift-auth:account-lockout:{$email}:" . now()->getTimestamp(),
            );

            $messageId = BirdFlock::dispatch($flight);

            return NotificationResult::success($messageId);
        } catch (RuntimeException $e) {
            logger()->error(
                'swift-auth.notification.account-lockout-failed',
                [
                    'email' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ],
            );

            return NotificationResult::failure($e->getMessage());
        }
    }

    /**
     * Returns password reset HTML email body.
     *
     * @param  string $resetUrl  Password reset URL.
     * @param  string $email     Recipient email.
     * @return string            HTML email content.
     */
    private function getPasswordResetHtml(
        string $resetUrl,
        string $email,
    ): string
    {
        return view(
            'swift-auth::emails.password_reset',
            [
                'resetUrl' => $resetUrl,
                'email' => $email,
            ],
        )->render();
    }

    /**
     * Returns password reset plain text email body.
     *
     * @param  string $resetUrl  Password reset URL.
     * @return string            Plain text email content.
     */
    private function getPasswordResetText(string $resetUrl): string
    {
        return view(
            'swift-auth::emails.password_reset_text',
            [
                'resetUrl' => $resetUrl,
            ],
        )->render();
    }

    /**
     * Returns email verification HTML email body.
     *
     * @param  string $verifyUrl  Email verification URL.
     * @param  string $email      Recipient email.
     * @return string             HTML email content.
     */
    private function getEmailVerificationHtml(
        string $verifyUrl,
        string $email,
    ): string
    {
        return view(
            'swift-auth::emails.verification',
            [
                'verifyUrl' => $verifyUrl,
                'email' => $email,
            ],
        )->render();
    }

    /**
     * Returns email verification plain text email body.
     *
     * @param  string $verifyUrl  Email verification URL.
     * @return string             Plain text email content.
     */
    private function getEmailVerificationText(string $verifyUrl): string
    {
        return view(
            'swift-auth::emails.verification_text',
            [
                'verifyUrl' => $verifyUrl,
            ],
        )->render();
    }

    /**
     * Returns account lockout HTML email body.
     *
     * @param  string $email    Recipient email.
     * @param  int    $minutes  Lockout duration in minutes.
     * @return string           HTML email content.
     */
    private function getAccountLockoutHtml(
        string $email,
        int $minutes,
    ): string
    {
        return view(
            'swift-auth::emails.account_lockout',
            [
                'email' => $email,
                'minutes' => $minutes,
            ],
        )->render();
    }

    /**
     * Returns account lockout plain text email body.
     *
     * @param  int    $minutes  Lockout duration in minutes.
     * @return string           Plain text email content.
     */
    private function getAccountLockoutText(int $minutes): string
    {
        return view(
            'swift-auth::emails.account_lockout_text',
            [
                'minutes' => $minutes,
            ],
        )->render();
    }
}
