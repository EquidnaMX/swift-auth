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

/**
 * Handles email notifications using Bird Flock messaging bus.
 */
final class NotificationService
{
    /**
     * Send password reset email.
     *
     * @param  string $email Recipient email address.
     * @param  string $token Password reset token.
     * @return string        Message ID from Bird Flock.
     */
    public function sendPasswordReset(string $email, string $token): string
    {
        $routePrefix = config('swift-auth.route_prefix', 'swift-auth');
        $resetUrl = url("/{$routePrefix}/password/{$token}?email=" . urlencode($email));

        $flight = new FlightPlan(
            channel: 'email',
            to: $email,
            subject: 'Password Reset Request',
            html: $this->getPasswordResetHtml($resetUrl, $email),
            text: $this->getPasswordResetText($resetUrl),
            idempotencyKey: "swift-auth:password-reset:{$email}:{$token}"
        );

        return BirdFlock::dispatch($flight);
    }

    /**
     * Send email verification email.
     *
     * @param  string $email Recipient email address.
     * @param  string $token Email verification token.
     * @return string        Message ID from Bird Flock.
     */
    public function sendEmailVerification(string $email, string $token): string
    {
        $routePrefix = config('swift-auth.route_prefix', 'swift-auth');
        $verifyUrl = url("/{$routePrefix}/email/verify/{$token}?email=" . urlencode($email));

        $flight = new FlightPlan(
            channel: 'email',
            to: $email,
            subject: 'Verify Your Email Address',
            html: $this->getEmailVerificationHtml($verifyUrl, $email),
            text: $this->getEmailVerificationText($verifyUrl),
            idempotencyKey: "swift-auth:email-verification:{$email}:{$token}"
        );

        return BirdFlock::dispatch($flight);
    }

    /**
     * Send account lockout notification.
     *
     * @param  string $email    Recipient email address.
     * @param  int    $duration Lockout duration in seconds.
     * @return string           Message ID from Bird Flock.
     */
    public function sendAccountLockout(string $email, int $duration): string
    {
        $minutes = (int) ceil($duration / 60);

        $flight = new FlightPlan(
            channel: 'email',
            to: $email,
            subject: 'Account Temporarily Locked',
            html: $this->getAccountLockoutHtml($email, $minutes),
            text: $this->getAccountLockoutText($minutes),
            idempotencyKey: "swift-auth:account-lockout:{$email}:" . now()->timestamp
        );

        return BirdFlock::dispatch($flight);
    }

    /**
     * Get password reset HTML email body.
     *
     * @param  string $resetUrl Password reset URL.
     * @param  string $email    Recipient email.
     * @return string           HTML email content.
     */
    private function getPasswordResetHtml(string $resetUrl, string $email): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Password Reset Request</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #2563eb;">Password Reset Request</h2>
                <p>You are receiving this email because we received a password reset request for your account: <strong>{$email}</strong></p>
                <p>Click the button below to reset your password:</p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="{$resetUrl}" style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset Password</a>
                </p>
                <p>Or copy and paste this URL into your browser:</p>
                <p style="word-break: break-all; color: #666;">{$resetUrl}</p>
                <p style="margin-top: 30px; color: #666; font-size: 14px;">
                    This password reset link will expire in 15 minutes.<br>
                    If you did not request a password reset, no further action is required.
                </p>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Get password reset plain text email body.
     *
     * @param  string $resetUrl Password reset URL.
     * @return string           Plain text email content.
     */
    private function getPasswordResetText(string $resetUrl): string
    {
        return <<<TEXT
        Password Reset Request

        You are receiving this email because we received a password reset request for your account.

        Reset your password by visiting this URL:
        {$resetUrl}

        This password reset link will expire in 15 minutes.

        If you did not request a password reset, no further action is required.
        TEXT;
    }

    /**
     * Get email verification HTML email body.
     *
     * @param  string $verifyUrl Email verification URL.
     * @param  string $email     Recipient email.
     * @return string            HTML email content.
     */
    private function getEmailVerificationHtml(string $verifyUrl, string $email): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Verify Your Email Address</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #2563eb;">Verify Your Email Address</h2>
                <p>Thank you for registering! Please verify your email address: <strong>{$email}</strong></p>
                <p>Click the button below to verify your email:</p>
                <p style="text-align: center; margin: 30px 0;">
                    <a href="{$verifyUrl}" style="background-color: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;">Verify Email</a>
                </p>
                <p>Or copy and paste this URL into your browser:</p>
                <p style="word-break: break-all; color: #666;">{$verifyUrl}</p>
                <p style="margin-top: 30px; color: #666; font-size: 14px;">
                    This verification link will expire in 24 hours.<br>
                    If you did not create an account, please ignore this email.
                </p>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Get email verification plain text email body.
     *
     * @param  string $verifyUrl Email verification URL.
     * @return string            Plain text email content.
     */
    private function getEmailVerificationText(string $verifyUrl): string
    {
        return <<<TEXT
        Verify Your Email Address

        Thank you for registering! Please verify your email address.

        Verify your email by visiting this URL:
        {$verifyUrl}

        This verification link will expire in 24 hours.

        If you did not create an account, please ignore this email.
        TEXT;
    }

    /**
     * Get account lockout HTML email body.
     *
     * @param  string $email   Recipient email.
     * @param  int    $minutes Lockout duration in minutes.
     * @return string          HTML email content.
     */
    private function getAccountLockoutHtml(string $email, int $minutes): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Account Temporarily Locked</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #dc2626;">Account Temporarily Locked</h2>
                <p>Your account (<strong>{$email}</strong>) has been temporarily locked due to multiple failed login attempts.</p>
                <p style="background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 15px; margin: 20px 0;">
                    <strong>Lockout Duration:</strong> {$minutes} minutes
                </p>
                <p>If this wasn't you, please contact support immediately as someone may be attempting to access your account.</p>
                <p style="margin-top: 30px; color: #666; font-size: 14px;">
                    Your account will automatically unlock after the lockout duration expires.
                </p>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Get account lockout plain text email body.
     *
     * @param  int $minutes Lockout duration in minutes.
     * @return string       Plain text email content.
     */
    private function getAccountLockoutText(int $minutes): string
    {
        return <<<TEXT
        Account Temporarily Locked

        Your account has been temporarily locked due to multiple failed login attempts.

        Lockout Duration: {$minutes} minutes

        If this wasn't you, please contact support immediately as someone may be attempting to access your account.

        Your account will automatically unlock after the lockout duration expires.
        TEXT;
    }
}
