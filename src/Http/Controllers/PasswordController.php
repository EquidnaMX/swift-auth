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

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Equidna\Toolkit\Exceptions\BadRequestException;
use Equidna\Toolkit\Exceptions\NotFoundException;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Inertia\Response;
use Equidna\SwiftAuth\Mail\PasswordResetMail;
use Equidna\SwiftAuth\Models\PasswordResetToken;
use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Traits\SelectiveRender;

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
     * @param  Request       $request  HTTP request context.
     * @return View|Response           Blade or Inertia response.
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
     * @param  Request                   $request  HTTP request with the email address.
     * @return RedirectResponse|JsonResponse       Context-aware success response.
     */
    public function sendResetLink(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['email' => 'required|email']);

        $email = strtolower($data['email']);

        $rateConfig = config('swift-auth.password_reset_rate_limit', []);
        $attempts = $rateConfig['attempts'] ?? 5;
        $decay = $rateConfig['decay_seconds'] ?? 60;

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
        if (RateLimiter::tooManyAttempts($ipKey, max(50, $attempts * 10))) {
            $availableIn = RateLimiter::availableIn($ipKey);

            return response()->json([
                'message' => 'Too many requests from this network. Try again in ' . $availableIn . ' seconds.'
            ], 429);
        }

        // Count attempt early to prevent enumeration races
        RateLimiter::hit($limiterKey, $decay);
        RateLimiter::hit($ipKey, $decay);

        // Generate token and persist (single active token per email)
        $token = hash('sha256', Str::random(64));

        PasswordResetToken::updateOrCreate(
            ['email' => $email],
            ['token' => $token, 'created_at' => now()]
        );

        try {
            Mail::to($email)->queue(new PasswordResetMail($email, $token));
        } catch (\Throwable $e) {
            // Log the error without including the full email address to reduce
            // exposure of potentially sensitive identifiers in logs.
            logger()->error('Failed to queue password reset mail', ['error' => $e->getMessage()]);
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
        $data = $request->validate([
            'email' => 'required|email|exists:' . $prefix . 'Users,email',
            'token' => 'required|string',
            'password' => 'required|min:6|confirmed',
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

        $reset = PasswordResetToken::where('email', $data['email'])
            ->where('token', $data['token'])
            ->first();

        if (!$reset) {
            // Increment the verify limiter to slow down brute-force.
            RateLimiter::hit($verifyLimiter, $verifyDecay);
            throw new BadRequestException('The reset token is invalid or has expired.');
        }

        // Enforce TTL
        $ttl = config('swift-auth.password_reset_ttl', 900);
        if (!$reset->created_at || now()->diffInSeconds($reset->created_at) > $ttl) {
            // remove expired token and reject
            $reset->delete();
            throw new BadRequestException('The reset token is invalid or has expired.');
        }

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw new NotFoundException('No user was found for the provided email.');
        }

        $user->update([
            'password' => Hash::make($data['password'])
        ]);

        $reset->delete();
        // Successful verification: clear the verify limiter for this target
        if (isset($verifyLimiter)) {
            RateLimiter::clear($verifyLimiter);
        }
        return ResponseHelper::success(
            message: 'Password updated successfully.',
            data: [
                'user_id' => $user->getKey(),
            ],
            forward_url: route('swift-auth.login.form'),
        );
    }
}
