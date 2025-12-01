<?php

/**
 * Exposes SwiftAuth login/logout flows for Laravel apps.
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Contracts\View\View;
use Equidna\Toolkit\Exceptions\UnauthorizedException;
use Equidna\Toolkit\Helpers\ResponseHelper;
use Inertia\Response;
use Equidna\SwiftAuth\Facades\SwiftAuth;
use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Services\NotificationService;
use Equidna\SwiftAuth\Traits\SelectiveRender;

/**
 * Orchestrates SwiftAuth authentication flows across login/logout endpoints.
 *
 * Presents blade or Inertia views as needed and emits context-aware toolkit responses.
 */
class AuthController extends Controller
{
    use SelectiveRender;

    /**
     * Shows the login form view.
     *
     * @param  Request       $request  HTTP request with context info.
     * @return View|Response           Blade or Inertia response.
     */
    public function showLoginForm(Request $request): View|Response
    {
        return $this->render(
            'swift-auth::login',
            'Login',
        );
    }

    /**
     * Authenticates the user using SwiftAuth.
     *
     * @param  Request                   $request             HTTP request with credentials.
     * @param  NotificationService       $notificationService Email notification service.
     * @return RedirectResponse|JsonResponse                 Context-aware success response.
     * @throws UnauthorizedException                         When credentials are invalid or account locked.
     */
    public function login(Request $request, NotificationService $notificationService): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:' . (int) config('swift-auth.password_min_length', 8),
        ]);

        // Rate limit login attempts per email to prevent brute-force attacks
        $loginLimiter = 'login:email:' . hash('sha256', strtolower($credentials['email']));
        $ipLimiter = 'login:ip:' . $request->ip();

        if (RateLimiter::tooManyAttempts($loginLimiter, 5)) {
            $availableIn = RateLimiter::availableIn($loginLimiter);
            throw new UnauthorizedException(
                'Too many login attempts. Please try again in ' . $availableIn . ' seconds.'
            );
        }

        if (RateLimiter::tooManyAttempts($ipLimiter, 20)) {
            $availableIn = RateLimiter::availableIn($ipLimiter);
            throw new UnauthorizedException(
                'Too many login attempts from this network. Please try again in ' . $availableIn . ' seconds.'
            );
        }

        $user = User::where('email', $credentials['email'])->first();

        // Check if account is locked
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $remainingSeconds = $user->locked_until->diffInSeconds(now());
            $remainingMinutes = ceil($remainingSeconds / 60);

            logger()->warning('swift-auth.login.account-locked', [
                'user_id' => $user->getKey(),
                'email' => $user->email,
                'locked_until' => $user->locked_until,
                'ip' => $request->ip(),
            ]);

            throw new UnauthorizedException(
                "Account is temporarily locked. Please try again in {$remainingMinutes} minutes."
            );
        }

        $driver = config('swift-auth.hash_driver');
        $valid = $driver
            ? Hash::driver($driver)->check($credentials['password'], $user?->password)
            : Hash::check($credentials['password'], $user?->password);

        if (!$user || !$valid) {
            // Increment failed login attempts
            if ($user && config('swift-auth.account_lockout.enabled', true)) {
                $user->failed_login_attempts++;
                $user->last_failed_login_at = now();

                $maxAttempts = config('swift-auth.account_lockout.max_attempts', 5);
                $lockoutDuration = config('swift-auth.account_lockout.lockout_duration', 900);

                if ($user->failed_login_attempts >= $maxAttempts) {
                    $user->locked_until = now()->addSeconds($lockoutDuration);
                    $user->save();

                    logger()->warning('swift-auth.login.account-locked-triggered', [
                        'user_id' => $user->getKey(),
                        'email' => $user->email,
                        'failed_attempts' => $user->failed_login_attempts,
                        'locked_until' => $user->locked_until,
                        'ip' => $request->ip(),
                    ]);

                    // Send lockout notification
                    try {
                        $notificationService->sendAccountLockout($user->email, $lockoutDuration);
                    } catch (\Throwable $e) {
                        logger()->error('swift-auth.login.lockout-notification-failed', [
                            'email' => $user->email,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    throw new UnauthorizedException(
                        'Account has been locked due to too many failed login attempts.'
                    );
                } else {
                    $user->save();
                }
            }

            // Increment rate limiter on failed login
            RateLimiter::hit($loginLimiter, 300); // 5 minutes
            RateLimiter::hit($ipLimiter, 300);

            logger()->warning('swift-auth.login.failed', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw new UnauthorizedException('Invalid credentials.');
        }

        // Reset failed login attempts and unlock account on successful login
        if ($user->failed_login_attempts > 0 || $user->locked_until) {
            $user->failed_login_attempts = 0;
            $user->locked_until = null;
            $user->last_failed_login_at = null;
            $user->save();
        }

        // Clear rate limiter on successful login
        RateLimiter::clear($loginLimiter);

        logger()->info('swift-auth.login.success', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        SwiftAuth::login($user);
        $request->session()->regenerate();

        return ResponseHelper::success(
            message: 'Login successful.',
            data: [
                'user_id' => $user->getKey(),
            ],
            forward_url: Config::get('swift-auth.success_url'),
        );
    }

    /**
     * Logs out the current user and clears the session.
     *
     * @param  Request                   $request  HTTP request carrying the session.
     * @return RedirectResponse|JsonResponse       Context-aware success response.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        $userId = SwiftAuth::id();

        SwiftAuth::logout();

        logger()->info('User logged out', [
            'user_id' => $userId,
            'ip' => $request->ip(),
        ]);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ResponseHelper::success(
            message: 'Logged out successfully.',
            data: null,
            forward_url: route('swift-auth.login.form'),
        );
    }
}
