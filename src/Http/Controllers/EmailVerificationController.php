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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Services\NotificationService;
use Equidna\Toolkit\Helpers\ResponseHelper;

/**
 * Manages email verification process.
 */
final class EmailVerificationController
{
    /**
     * Sends email verification link.
     *
     * Enforces rate limiting, generates secure token, and dispatches verification email.
     *
     * @param  Request              $request              HTTP request.
     * @param  NotificationService  $notificationService  Email service.
     * @return JsonResponse                               Success or error response.
     */
    public function send(Request $request, NotificationService $notificationService): JsonResponse
    {
        $rawEmail = $request->input('email');
        $email = is_string($rawEmail) ? trim($rawEmail) : '';

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ResponseHelper::badRequest(
                message: 'Invalid email address.'
            );
        }

        // Rate limit per IP to prevent abuse
        $ipLimiter = 'email-verification:ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($ipLimiter, 5)) {
            $seconds = RateLimiter::availableIn($ipLimiter);
            logger()->warning('swift-auth.email-verification.ip-rate-limit-exceeded', [
                'ip' => $request->ip(),
                'email' => $email,
            ]);

            return ResponseHelper::tooManyRequests(
                message: "Too many verification requests. Please try again in {$seconds} seconds."
            );
        }

        $rateLimitKey = 'email-verification:' . sha1($email);
        /** @var array{attempts?:int,decay_seconds?:int}|mixed $rateLimitConfig */
        $rateLimitConfig = config('swift-auth.email_verification.resend_rate_limit', []);
        $attempts = (int) ($rateLimitConfig['attempts'] ?? 3);
        $decaySeconds = (int) ($rateLimitConfig['decay_seconds'] ?? 300);

        if (RateLimiter::tooManyAttempts($rateLimitKey, $attempts)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            logger()->warning('swift-auth.email-verification.rate-limit-exceeded', [
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return ResponseHelper::tooManyRequests(
                message: "Too many verification emails sent. Please try again in {$seconds} seconds."
            );
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            RateLimiter::hit($rateLimitKey, $decaySeconds);
            return ResponseHelper::notFound(
                message: 'User not found.'
            );
        }

        if ($user->email_verified_at) {
            return ResponseHelper::badRequest(
                message: 'Email already verified.'
            );
        }

        $token = Str::random(64);
        $user->email_verification_token = hash('sha256', $token);
        $user->email_verification_sent_at = now();
        $user->save();

        $result = $notificationService->sendEmailVerification($email, $token);

        if (!$result->success) {
            return ResponseHelper::error(
                message: 'Failed to send verification email. Please try again later.'
            );
        }

        RateLimiter::hit($rateLimitKey, $decaySeconds);
        RateLimiter::hit($ipLimiter, 60); // 1 minute decay

        logger()->info('swift-auth.email-verification.sent', [
            'user_id' => $user->id_user,
            'email' => $email,
            'message_id' => $result->messageId,
            'ip' => $request->ip(),
        ]);

        return ResponseHelper::success(
            message: 'Verification email sent successfully.',
            data: null,
        );
    }

    /**
     * Verifies email with token.
     *
     * Validates token, checks TTL, and marks email as verified on success.
     *
     * @param  Request       $request  HTTP request with token and email.
     * @return JsonResponse            Success or error response.
     */
    public function verify(Request $request): JsonResponse
    {
        $tokenRaw = $request->route('token');
        $token = is_string($tokenRaw) ? $tokenRaw : '';
        $rawQueryEmail = $request->query('email');
        $email = is_string($rawQueryEmail) ? trim($rawQueryEmail) : '';

        if ($token === '' || $email === '') {
            return ResponseHelper::badRequest(
                message: 'Invalid verification link.'
            );
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

            return ResponseHelper::badRequest(
                message: 'Invalid or expired verification token.'
            );
        }

        $ttl = config('swift-auth.email_verification.token_ttl', 86400);
        $sentAt = $user->email_verification_sent_at;
        if ($sentAt && $sentAt->addSeconds($ttl)->isPast()) {
            logger()->warning('swift-auth.email-verification.token-expired', [
                'user_id' => $user->id_user,
                'email' => $email,
                'sent_at' => $sentAt,
            ]);

            return ResponseHelper::badRequest(
                message: 'Verification token has expired.'
            );
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

        return ResponseHelper::success(
            message: 'Email verified successfully.',
        );
    }
}
