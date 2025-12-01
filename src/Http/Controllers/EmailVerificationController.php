<?php

/**
 * Handles email verification flow.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Controllers
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Http\Controllers;

use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Services\NotificationService;
use Equidna\Toolkit\Support\ResponseHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Manages email verification process.
 */
final class EmailVerificationController
{
    /**
     * Send email verification link.
     *
     * @param  Request             $request             HTTP request.
     * @param  NotificationService $notificationService Email service.
     * @return JsonResponse                             Success or error response.
     */
    public function send(Request $request, NotificationService $notificationService): JsonResponse
    {
        $email = $request->input('email');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ResponseHelper::error('Invalid email address.', [], 400);
        }

        $rateLimitKey = 'email-verification:' . sha1($email);
        $rateLimitConfig = config('swift-auth.email_verification.resend_rate_limit');

        if (RateLimiter::tooManyAttempts($rateLimitKey, $rateLimitConfig['attempts'])) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            logger()->warning('swift-auth.email-verification.rate-limit-exceeded', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return ResponseHelper::error(
                "Too many verification emails sent. Please try again in {$seconds} seconds.",
                [],
                429
            );
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            RateLimiter::hit($rateLimitKey, $rateLimitConfig['decay_seconds']);
            return ResponseHelper::error('User not found.', [], 404);
        }

        if ($user->email_verified_at) {
            return ResponseHelper::error('Email already verified.', [], 400);
        }

        $token = Str::random(64);
        $user->email_verification_token = hash('sha256', $token);
        $user->email_verification_sent_at = now();
        $user->save();

        try {
            $messageId = $notificationService->sendEmailVerification($email, $token);

            RateLimiter::hit($rateLimitKey, $rateLimitConfig['decay_seconds']);

            logger()->info('swift-auth.email-verification.sent', [
                'user_id' => $user->id_user,
                'email' => $email,
                'message_id' => $messageId,
                'ip' => $request->ip(),
            ]);

            return ResponseHelper::success([
                'message' => 'Verification email sent successfully.',
            ]);
        } catch (\Throwable $e) {
            logger()->error('swift-auth.email-verification.send-failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return ResponseHelper::error('Failed to send verification email.', [], 500);
        }
    }

    /**
     * Verify email with token.
     *
     * @param  Request      $request HTTP request with token and email.
     * @return JsonResponse          Success or error response.
     */
    public function verify(Request $request): JsonResponse
    {
        $token = $request->route('token');
        $email = $request->query('email');

        if (!$token || !$email) {
            return ResponseHelper::error('Invalid verification link.', [], 400);
        }

        $hashedToken = hash('sha256', $token);
        $user = User::where('email', $email)
            ->where('email_verification_token', $hashedToken)
            ->first();

        if (!$user) {
            logger()->warning('swift-auth.email-verification.invalid-token', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return ResponseHelper::error('Invalid or expired verification token.', [], 400);
        }

        $ttl = config('swift-auth.email_verification.token_ttl', 86400);
        if ($user->email_verification_sent_at->addSeconds($ttl)->isPast()) {
            logger()->warning('swift-auth.email-verification.token-expired', [
                'user_id' => $user->id_user,
                'email' => $email,
                'sent_at' => $user->email_verification_sent_at,
            ]);

            return ResponseHelper::error('Verification token has expired.', [], 400);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->email_verification_sent_at = null;
        $user->save();

        logger()->info('swift-auth.email-verification.verified', [
            'user_id' => $user->id_user,
            'email' => $email,
            'ip' => $request->ip(),
        ]);

        return ResponseHelper::success([
            'message' => 'Email verified successfully.',
        ]);
    }
}
