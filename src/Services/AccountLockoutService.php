<?php

/**
 * Manages account lockout logic for failed login attempts.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Services
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Services;

use Equidna\SwiftAuth\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Models\User;

/**
 * Handles failed login attempt tracking and account lockout enforcement.
 *
 * Coordinates with UserRepository and NotificationService to enforce
 * security policies around repeated authentication failures.
 */
final class AccountLockoutService
{
    /**
     * Creates a lockout service with required dependencies.
     *
     * @param  UserRepositoryInterface $userRepository       User persistence layer.
     * @param  NotificationService     $notificationService  Email notification service.
     */
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private NotificationService $notificationService
    ) {
        // ...
    }

    /**
     * Checks if a user account is currently locked.
     *
     * @param  User $user  User instance to check.
     * @return bool        True if locked and lock period not expired.
     */
    public function isLocked(User $user): bool
    {
        return $user->locked_until && $user->locked_until->isFuture();
    }

    /**
     * Returns remaining lockout time in minutes.
     *
     * @param  User $user  User instance with active lockout.
     * @return int         Remaining minutes (rounded up).
     */
    public function getRemainingLockoutMinutes(User $user): int
    {
        if (!$user->locked_until) {
            return 0;
        }

        $remainingSeconds = $user->locked_until->diffInSeconds(now());
        return (int) ceil($remainingSeconds / 60);
    }

    /**
     * Records a failed login attempt and triggers lockout if threshold reached.
     *
     * Increments failed attempt counter, locks account if max attempts exceeded,
     * and sends lockout notification email.
     *
     * @param  User   $user  User who failed authentication.
     * @param  string $ip    IP address of failed attempt.
     * @return bool          True if account was locked, false otherwise.
     */
    public function recordFailedAttempt(
        User $user,
        string $ip,
    ): bool {
        if (!config('swift-auth.account_lockout.enabled', true)) {
            return false;
        }

        $this->refreshAttemptsAfterInactivity($user);

        $this->userRepository->incrementFailedLogins($user);

        $maxAttempts = config('swift-auth.account_lockout.max_attempts', 5);
        $lockoutDuration = config('swift-auth.account_lockout.lockout_duration', 900);

        if ($user->failed_login_attempts >= $maxAttempts) {
            $this->userRepository->lockAccount($user, $lockoutDuration);

            logger()->warning('swift-auth.login.account-locked-triggered', [
                'user_id' => $user->getKey(),
                'email' => $user->email,
                'failed_attempts' => $user->failed_login_attempts,
                'locked_until' => $user->locked_until,
                'ip' => $ip,
            ]);

            // Send lockout notification (resilient to email failures)
            $this->notificationService->sendAccountLockout($user->email, $lockoutDuration);

            return true;
        }

        return false;
    }

    /**
     * Clears failed login attempts and lockout state on successful login.
     *
     * @param  User $user  User who successfully authenticated.
     * @return void
     */
    public function resetAttempts(User $user): void
    {
        if ($user->failed_login_attempts > 0 || $user->locked_until) {
            $this->userRepository->resetFailedLogins($user);
        }
    }

    /**
     * Clears lockout counters when the idle window has elapsed.
     *
     * @param  User $user  User to refresh.
     * @return void
     */
    public function refreshAttemptsAfterInactivity(User $user): void
    {
        $resetAfter = (int) config('swift-auth.account_lockout.reset_after', 0);

        if ($resetAfter <= 0) {
            return;
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return;
        }

        if (!$user->last_failed_login_at) {
            return;
        }

        if ($user->last_failed_login_at->diffInSeconds(now()) >= $resetAfter) {
            $this->userRepository->resetFailedLogins($user);
        }
    }
}
