<?php

/**
 * Implements SwiftAuth's session-backed authentication service helpers.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Services
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\Store as Session;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

use Equidna\SwiftAuth\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Models\User;

/**
 * Provides session-based authentication handling for users.
 *
 * Handles login, logout, session checks, and user retrieval using Laravel's session storage.
 */
class SwiftSessionAuth
{
    protected Session $session;
    protected string $sessionKey = 'swift_auth_user_id';
    protected string $lastActivityKey = 'swift_auth_last_activity';
    protected string $loginTimeKey = 'swift_auth_login_time';
    protected string $rememberCookie = 'swift_auth_remember';

    /**
     * Creates a new SwiftSessionAuth instance.
     *
     * @param  Session                  $session         Laravel session store instance.
     * @param  UserRepositoryInterface  $userRepository  User data access layer.
     */
    public function __construct(
        Session $session,
        private UserRepositoryInterface $userRepository
    ) {
        $this->session = $session;
    }

    /**
     * Logs in a user by storing their ID in the session.
     *
     * @param  User $user  User instance to authenticate.
     * @return void
     */
    public function login(User $user, bool $remember = false): void
    {
        $now = CarbonImmutable::now();

        $this->session->put($this->sessionKey, $user->getKey());
        $this->session->put($this->loginTimeKey, $now->getTimestamp());
        $this->session->put($this->lastActivityKey, $now->getTimestamp());

        if ($remember && config('swift-auth.session.remember_me.enabled', true)) {
            $this->createRememberToken($user, $now);
        } else {
            $this->clearRememberCookie();
        }
    }

    /**
     * Logs out the user by removing their ID from the session.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->session->forget($this->sessionKey);
        $this->session->forget($this->lastActivityKey);
        $this->session->forget($this->loginTimeKey);
        $this->clearRememberCookie();
    }

    /**
     * Determines if a user is currently authenticated via session.
     *
     * @return bool  True if authenticated, false otherwise.
     */
    public function check(): bool
    {
        $now = CarbonImmutable::now();
        $idleTimeout = config('swift-auth.session.idle_timeout');
        $absoluteTimeout = config('swift-auth.session.absolute_timeout');

        if ($this->session->has($this->sessionKey)) {
            $lastActivity = $this->session->get($this->lastActivityKey);
            $loginTime = $this->session->get($this->loginTimeKey);

            if (is_int($idleTimeout) && $lastActivity && $now->getTimestamp() - $lastActivity >= $idleTimeout) {
                $this->logout();
                return false;
            }

            if (is_int($absoluteTimeout) && $loginTime && $now->getTimestamp() - $loginTime >= $absoluteTimeout) {
                $this->logout();
                return false;
            }

            $this->session->put($this->lastActivityKey, $now->getTimestamp());
            return true;
        }

        return $this->attemptRememberReauthentication($now);
    }

    /**
     * Returns the authenticated user's ID from the session.
     *
     * @return int|null  User ID or null if not authenticated.
     */
    public function id(): null|int
    {
        return $this->session->get($this->sessionKey);
    }

    /**
     * Returns the authenticated User model instance, or null if not found.
     *
     * @return User|null  Authenticated user or null.
     */
    public function user(): null|User
    {
        $id = $this->id();
        return $id ? $this->userRepository->findById($id) : null;
    }

    /**
     * Returns the authenticated User model instance or throws exception if not found.
     *
     * @return User                    Authenticated user instance.
     * @throws ModelNotFoundException  When user ID not in session or user record not found.
     */
    public function userOrFail(): User
    {
        $id = $this->id();

        if (!$id || !($user = $this->userRepository->findById($id))) {
            throw new ModelNotFoundException("User not found");
        }

        return $user;
    }

    /**
     * Checks if the authenticated user is allowed to perform the given action(s).
     *
     * @param  string|array<string,mixed> $actions  Action or list of actions to validate.
     * @return bool                                  True if user has permission for at least one action.
     */
    public function canPerformAction(string|array $actions): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        $available = $user->availableActions();

        if (in_array('sw-admin', $available)) {
            return true;
        }

        return is_array($actions)
            ? count(array_intersect($available, $actions)) > 0
            : in_array($actions, $available);
    }

    /**
     * Checks if the authenticated user has any of the given roles.
     *
     * @param  string|array<string,mixed> $roles  Role name(s) to check.
     * @return bool                                True if user has at least one role.
     */
    public function hasRole(string|array $roles): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        // sw-admin action implies all role checks pass
        if ($this->canPerformAction('sw-admin')) {
            return true;
        }

        return $user->hasRoles($roles);
    }

    /**
     * Attempts to reauthenticate a user using a remember-me cookie.
     */
    protected function attemptRememberReauthentication(CarbonImmutable $now): bool
    {
        if (!config('swift-auth.session.remember_me.enabled', true)) {
            return false;
        }

        $rememberValue = Cookie::get($this->rememberCookie);

        if (!$rememberValue || !str_contains($rememberValue, '|')) {
            return false;
        }

        [$userId, $token, $expires] = array_pad(explode('|', $rememberValue, 3), 3, null);

        if (!$userId || !$token || !$expires) {
            $this->clearRememberCookie();
            return false;
        }

        if ($now->getTimestamp() >= (int) $expires) {
            $this->purgeRememberToken((int) $userId);
            return false;
        }

        $user = $this->userRepository->findById((int) $userId);

        if (!$user || !hash_equals((string) $user->remember_token, hash('sha256', $token))) {
            $this->purgeRememberToken((int) $userId);
            return false;
        }

        $this->login($user, false);

        if (config('swift-auth.session.remember_me.rotate', true)) {
            $this->createRememberToken($user, $now);
        }

        return true;
    }

    /**
     * Creates and queues a remember-me token and cookie for the user.
     */
    protected function createRememberToken(User $user, CarbonImmutable $now): void
    {
        $ttl = (int) config('swift-auth.session.remember_me.ttl', 60 * 60 * 24 * 14);
        $token = Str::random(60);
        $hashedToken = hash('sha256', $token);
        $user->remember_token = $hashedToken;
        $user->save();

        $expiresAt = $now->addSeconds($ttl);
        $minutes = max(1, (int) ceil($ttl / 60));

        Cookie::queue(cookie(
            $this->rememberCookie,
            implode('|', [$user->getKey(), $token, $expiresAt->getTimestamp()]),
            $minutes,
            null,
            null,
            false,
            true,
            false,
            'Strict',
        ));
    }

    /**
     * Clears remember-me state for a user and cookie.
     */
    protected function purgeRememberToken(int $userId): void
    {
        $user = $this->userRepository->findById($userId);

        if ($user) {
            $user->remember_token = null;
            $user->save();
        }

        $this->clearRememberCookie();
    }

    /**
     * Queues removal of the remember-me cookie.
     */
    protected function clearRememberCookie(): void
    {
        Cookie::queue(Cookie::forget($this->rememberCookie));
    }
}
