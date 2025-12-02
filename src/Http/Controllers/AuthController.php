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

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

use Inertia\Response;

use Equidna\SwiftAuth\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Facades\SwiftAuth;
use Equidna\SwiftAuth\Http\Requests\LoginRequest;
use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Services\AccountLockoutService;
use Equidna\SwiftAuth\Services\NotificationService;
use Equidna\SwiftAuth\Traits\SelectiveRender;
use Equidna\Toolkit\Exceptions\UnauthorizedException;
use Equidna\Toolkit\Helpers\ResponseHelper;

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
     * @param  Request        $request  HTTP request with context info.
     * @return View|Response            Blade or Inertia response.
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
     * Enforces rate limiting per email and IP, checks account lockout status, validates credentials,
     * and manages login attempt tracking with automatic account lockout.
     *
     * @param  \Equidna\SwiftAuth\Http\Requests\LoginRequest $request         Validated login request.
     * @param  UserRepositoryInterface                          $userRepository  User data access layer.
     * @param  AccountLockoutService                            $lockoutService  Lockout management service.
     * @return RedirectResponse|JsonResponse                                   Context-aware success response.
     * @throws UnauthorizedException                                           When credentials invalid or account locked.
     */
    public function login(
        LoginRequest $request,
        UserRepositoryInterface $userRepository,
        AccountLockoutService $lockoutService
    ): RedirectResponse|JsonResponse {
        /** @var array{email:string,password:string} $credentials */
        $credentials = $request->validated();

        $loginRateLimit = config('swift-auth.login_rate_limits', []);
        $emailLimiterConfig = is_array($loginRateLimit['email'] ?? null)
            ? $loginRateLimit['email']
            : [];
        $ipLimiterConfig = is_array($loginRateLimit['ip'] ?? null)
            ? $loginRateLimit['ip']
            : [];

        // Rate limit login attempts per email to prevent brute-force attacks
        $loginLimiter = 'login:email:' . hash('sha256', strtolower($credentials['email']));
        $ipLimiter = 'login:ip:' . $request->ip();

        $emailAttempts = (int) ($emailLimiterConfig['attempts'] ?? 5);
        $emailDecay = (int) ($emailLimiterConfig['decay_seconds'] ?? 300);

        if (RateLimiter::tooManyAttempts($loginLimiter, $emailAttempts)) {
            $availableIn = RateLimiter::availableIn($loginLimiter);
            throw new UnauthorizedException(
                'Too many login attempts. Please try again in ' . $availableIn . ' seconds.'
            );
        }

        $ipAttempts = (int) ($ipLimiterConfig['attempts'] ?? 20);
        $ipDecay = (int) ($ipLimiterConfig['decay_seconds'] ?? 300);

        if (RateLimiter::tooManyAttempts($ipLimiter, $ipAttempts)) {
            $availableIn = RateLimiter::availableIn($ipLimiter);
            throw new UnauthorizedException(
                'Too many login attempts from this network. Please try again in ' . $availableIn . ' seconds.'
            );
        }

        $user = $userRepository->findByEmail($credentials['email']);

        if ($user) {
            $lockoutService->refreshAttemptsAfterInactivity($user);
        }

        // Check if account is locked
        if ($user && $lockoutService->isLocked($user)) {
            $remainingMinutes = $lockoutService->getRemainingLockoutMinutes($user);

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

        // Always perform hash check to prevent timing attacks (constant-time comparison)
        // Use dummy bcrypt hash when user doesn't exist to maintain consistent timing
        $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // bcrypt of 'password'
        $passwordToCheck = $user ? $user->password : $dummyHash;

        $driver = config('swift-auth.hash_driver');
        $driver = is_string($driver) ? $driver : null;
        if ($driver) {
            /** @var \Illuminate\Contracts\Hashing\Hasher $hasher */
            $hasher = Hash::driver($driver);
            $valid = $hasher->check($credentials['password'], $passwordToCheck);
        } else {
            $valid = Hash::check($credentials['password'], $passwordToCheck);
        }

        if (!$user || !$valid) {
            // Record failed attempt and trigger lockout if threshold reached
            if ($user) {
                $lockoutService->refreshAttemptsAfterInactivity($user);

                $ip = (string) ($request->ip() ?? '');
                $wasLocked = $lockoutService->recordFailedAttempt($user, $ip);

                if ($wasLocked) {
                    throw new UnauthorizedException(
                        'Account has been locked due to too many failed login attempts.'
                    );
                }
            }

            // Increment rate limiter on failed login
            RateLimiter::hit($loginLimiter, $emailDecay); // 5 minutes
            RateLimiter::hit($ipLimiter, $ipDecay);

            logger()->warning('swift-auth.login.failed', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw new UnauthorizedException('Invalid credentials.');
        }

        // Reset failed login attempts on successful login
        $lockoutService->resetAttempts($user);

        // Clear rate limiter on successful login
        RateLimiter::clear($loginLimiter);

        $mfaConfig = config('swift-auth.mfa', []);

        if ($this->shouldRequestMfa($mfaConfig)) {
            $driver = $this->resolveMfaDriver($mfaConfig);
            $verificationUrl = (string) ($mfaConfig['verification_url'] ?? '');

            SwiftAuth::startMfaChallenge(
                user: $user,
                driver: $driver,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            logger()->info('swift-auth.login.mfa-required', [
                'user_id' => $user->getKey(),
                'driver' => $driver,
                'ip' => $request->ip(),
            ]);

            return ResponseHelper::success(
                message: 'Additional verification required.',
                data: [
                    'mfa_required' => true,
                    'driver' => $driver,
                    'verification_url' => $verificationUrl,
                    'user_id' => $user->getKey(),
                ],
            );
        }

        logger()->info('swift-auth.login.success', [
            'user_id' => $user->getKey(),
            'email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $remember = (bool) $request->boolean('remember', false);

        $loginResult = SwiftAuth::login(
            user: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            deviceName: (string) $request->header('X-Device-Name', ''),
            remember: $remember,
        );

        /** @var JsonResponse|RedirectResponse|string $response */
        $response = ResponseHelper::success(
            message: 'Login successful.',
            data: [
                'user_id' => $user->getKey(),
                'evicted_session_ids' => $loginResult['evicted_session_ids'] ?? [],
            ],
            forward_url: Config::get('swift-auth.success_url'),
        );

        // Normalize potential non-response return into JsonResponse
        if (is_string($response)) {
            $response = response()->json([
                'message' => 'Login successful.',
                'user_id' => $user->getKey(),
                'forward_url' => Config::get('swift-auth.success_url'),
                'evicted_session_ids' => $loginResult['evicted_session_ids'] ?? [],
            ]);
        }
        /** @var JsonResponse|RedirectResponse $response */
        return $response;
    }

    /**
     * Logs out the current user and clears the session.
     *
     * @param  Request                    $request  HTTP request carrying the session.
     * @return RedirectResponse|JsonResponse        Context-aware success response.
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

        /** @var JsonResponse|RedirectResponse|string $response */
        $response = ResponseHelper::success(
            message: 'Logged out successfully.',
            data: null,
            forward_url: route('swift-auth.login.form'),
        );

        if (is_string($response)) {
            $response = response()->json([
                'message' => 'Logged out successfully.',
                'forward_url' => route('swift-auth.login.form'),
            ]);
        }
        /** @var JsonResponse|RedirectResponse $response */
        return $response;
    }

    /**
     * Determines whether an MFA challenge should be triggered.
     *
     * @param  array<string, mixed> $mfaConfig  MFA configuration values.
     * @return bool
     */
    private function shouldRequestMfa(array $mfaConfig): bool
    {
        return (bool) ($mfaConfig['enabled'] ?? false);
    }

    /**
     * Resolves the MFA driver name from configuration.
     *
     * @param  array<string, mixed> $mfaConfig  MFA configuration values.
     * @return string
     */
    private function resolveMfaDriver(array $mfaConfig): string
    {
        $driver = (string) ($mfaConfig['driver'] ?? 'otp');

        return in_array($driver, ['otp', 'webauthn'], true)
            ? $driver
            : 'otp';
    }
}
