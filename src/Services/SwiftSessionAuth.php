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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\Store as Session;

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
    public function login(User $user): void
    {
        $this->session->put($this->sessionKey, $user->getKey());
    }

    /**
     * Logs out the user by removing their ID from the session.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->session->forget($this->sessionKey);
    }

    /**
     * Determines if a user is currently authenticated via session.
     *
     * @return bool  True if authenticated, false otherwise.
     */
    public function check(): bool
    {
        return $this->session->has($this->sessionKey);
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
}
