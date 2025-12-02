<?php

/**
 * Handles password reset flows for SwiftAuth consumers.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Controllers
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

use Inertia\Response;

use Equidna\SwiftAuth\Models\PasswordResetToken;
use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Services\NotificationService;
use Equidna\SwiftAuth\Services\PasswordPolicy;
use Equidna\SwiftAuth\Traits\SelectiveRender;
use Equidna\Toolkit\Exceptions\BadRequestException;
use Equidna\Toolkit\Exceptions\NotFoundException;
use Equidna\Toolkit\Helpers\ResponseHelper;

/**
 * Coordinates SwiftAuth password reset UX, from request to completion.
 *
 * Renders multi-context views and interacts with reset tokens plus notification mailers.
 */
class PasswordController extends Controller
{
    use SelectiveRender;

    /**
     * Shows the password reset request form.
     *
     * @param  Request        $request  HTTP request context.
     * @return View|Response            Blade or Inertia response.
     */
    public function showRequestForm(Request $request): View|Response
    {
        return $this->render(
            'swift-auth::password.email',
            'password/Request',
        );
    }

    /**
     * Sends password reset instructions to the provided email.
     *
     * Enforces rate limiting per email and IP to prevent abuse, generates a secure token,
     * and dispatches reset email via notification service.
     *
     * @param  Request                    $request              HTTP request with the email address.
     * @param  NotificationService        $notificationService  Email notification service.
     * @return RedirectResponse|JsonResponse                    Context-aware success response.
     * @throws BadRequestException                              When email dispatch fails.
     */
    public function sendResetLink(
        Request $request,
        NotificationService $notificationService,
    ): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['email' => 'required|email']);

        $email = strtolower($data['email']);

        /** @var array{attempts?:int,decay_seconds?:int}|mixed $rateConfig */
        $rateConfig = config('swift-auth.password_reset_rate_limit', []);
        $attempts = (int) ($rateConfig['attempts'] ?? 5);
        $decay = (int) ($rateConfig['decay_seconds'] ?? 60);

        // Limit requests per-target (email) to prevent abuse and enumeration.
        $limiterKey = 'password-reset:email:' . hash('sha256', $email);

        // Additional soft limit per-IP to curtail mass scanning.
        $ipKey = 'password-reset:ip:' . $request->ip();

        // If too many attempts for this email, return a 429 with retry information
        if (RateLimiter::tooManyAttempts($limiterKey, $attempts)) {
            $availableIn = RateLimiter::availableIn($limiterKey);

            return response()->json([
                'message' => 'Too many password reset attempts. Try again in ' . $availableIn . ' seconds.'
            ], 429);
        }

        // IP-level protection: high threshold to reduce noise but stop large scans
        $ipThreshold = max(50, $attempts * 10);
        if (RateLimiter::tooManyAttempts($ipKey, $ipThreshold)) {
            $availableIn = RateLimiter::availableIn($ipKey);

            return response()->json([
                'message' => 'Too many requests from this network. Try again in ' . $availableIn . ' seconds.'
            ], 429);
        }

        // Count attempt early to prevent enumeration races
        RateLimiter::hit($limiterKey, $decay);
        RateLimiter::hit($ipKey, $decay);

        // Generate raw token and hash for storage
        $rawToken = Str::random(64);
        $hashedToken = hash('sha256', $rawToken);

        PasswordResetToken::updateOrCreate(
            ['email' => $email],
            ['token' => $hashedToken, 'created_at' => now()]
        );

        try {
            $messageId = $notificationService->sendPasswordReset($email, $rawToken);

            logger()->info('swift-auth.password-reset.email-sent', [
                'email_hash' => hash('sha256', $email),
                'message_id' => $messageId,
                'ip' => $request->ip(),
            ]);
        } catch (\Throwable $e) {
            logger()->error('swift-auth.password-reset.send-failed', ['error' => $e->getMessage()]);
            throw new BadRequestException('Unable to process password reset at this time.');
        }

        return ResponseHelper::success(
            message: 'Password reset instructions sent (if the email exists).',
            data: ['email' => $email],
            forward_url: route('swift-auth.password.request.sent'),
        );
    }

    /**
     * Shows the reset form populated with the token.
     *
     * @param  Request       $request  HTTP request context.
     * @param  string        $token    Reset token value.
     * @return View|Response           Blade or Inertia response.
     */
    public function showResetForm(
        Request $request,
        string $token,
    ): View|Response {
        return $this->render(
            'swift-auth::password.reset',
            'password/Reset',
            [
                'token' => $token,
                'email' => $request->input('email'),
            ],
        );
    }

    /**
     * Shows the confirmation page after emailing reset instructions.
     *
     * @param  Request       $request  HTTP request context.
     * @return View|Response           Blade or Inertia response.
     */
    public function showRequestSent(Request $request): View|Response
    {
        return $this->render(
            'swift-auth::password.request_sent',
            'password/RequestSent',
        );
    }

    /**
     * Updates the password when the token is valid.
     *
     * @param  Request                   $request  HTTP request containing token, email, and password.
     * @return RedirectResponse|JsonResponse       Context-aware success response.
     * @throws BadRequestException                 When token validation fails.
     * @throws NotFoundException                   When the email does not exist.
     */
    public function resetPassword(Request $request): RedirectResponse|JsonResponse
    {
        $prefix = config('swift-auth.table_prefix', '');
        $passwordRules = PasswordPolicy::rules();

        $data = $request->validate([
            'email' => 'required|email|exists:' . $prefix . 'Users,email',
            'token' => 'required|string',
            'password' => array_merge(
                ['required', 'string', 'confirmed'],
                $passwordRules,
            ),
        ]);

        // Protect verification endpoint from brute-force attempts.
        $verifyLimiter = 'password-reset:verify:' . hash('sha256', strtolower($data['email']));
        $verifyAttempts = config('swift-auth.password_reset_verify_attempts', 10);
        $verifyDecay = config('swift-auth.password_reset_verify_decay_seconds', 3600);

        if (RateLimiter::tooManyAttempts($verifyLimiter, $verifyAttempts)) {
            $availableIn = RateLimiter::availableIn($verifyLimiter);
            return response()->json([
                'message' => 'Too many verification attempts. Try again in ' . $availableIn . ' seconds.'
            ], 429);
        }

        /** @var array{email:string} $data */
        $reset = PasswordResetToken::where('email', $data['email'])->first();

        // Use constant-time comparison to prevent timing attacks
        if (!$reset || !hash_equals($reset->token, $data['token'])) {
            // Increment the verify limiter to slow down brute-force.
            RateLimiter::hit($verifyLimiter, $verifyDecay);
            throw new BadRequestException('The reset token is invalid or has expired.');
        }

        // Enforce TTL
        $ttl = config('swift-auth.password_reset_ttl', 900);
        /** @var \Illuminate\Support\Carbon|null $createdAt */
        $createdAt = $reset->created_at;
        if (!$createdAt || $createdAt->diffInSeconds(now()) > $ttl) {
            // remove expired token and reject
            $reset->delete();
            throw new BadRequestException('The reset token is invalid or has expired.');
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw new NotFoundException('No user was found for the provided email.');
        }

        $driver = config('swift-auth.hash_driver');
        $driver = is_string($driver) ? $driver : null;
        if ($driver) {
            /** @var \Illuminate\Contracts\Hashing\Hasher $hasher */
            $hasher = Hash::driver($driver);
            $hashed = $hasher->make($data['password']);
        } else {
            $hashed = Hash::make($data['password']);
        }

        $user->update([
            'password' => $hashed
        ]);

        logger()->info('Password reset completed', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        $reset->delete();
        // Successful verification: clear the verify limiter for this target
        RateLimiter::clear($verifyLimiter);
        return ResponseHelper::success(
            message: 'Password updated successfully.',
            data: [
                'user_id' => $user->getKey(),
            ],
            forward_url: route('swift-auth.login.form'),
        );
    }

}
