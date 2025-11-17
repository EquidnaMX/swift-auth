<?php

namespace Equidna\SwifthAuth\Services;

use Equidna\SwifthAuth\Models\User;
use Illuminate\Session\Store as Session;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Class SwiftSessionAuth
 *
 * Provides session-based authentication handling for users.
 * This class handles login, logout, session checks, and user retrieval
 * using Laravel's session storage.
 */
class SwiftSessionAuth
{
    /**
     * The session store instance.
     *
     * @var Session
     */
    protected Session $session;

    /**
     * The session key used to store the authenticated user ID.
     *
     * @var string
     */
    protected string $sessionKey = 'swift_auth_user_id';

    /**
     * Create a new SwiftSessionAuth instance.
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Log in a user by storing their ID in the session.
     *
     * @param User $user
     * @return void
     */
    public function login(User $user): void
    {
        $this->session->put($this->sessionKey, $user->getKey());
    }

    /**
     * Log out the user by removing their ID from the session.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->session->forget($this->sessionKey);
    }

    /**
     * Determine if a user is currently authenticated via session.
     *
     * @return bool
     */
    public function check(): bool
    {
        return $this->session->has($this->sessionKey);
    }

    /**
     * Get the authenticated user's ID from the session.
     *
     * @return int|null
     */
    public function id(): null|int
    {
        return $this->session->get($this->sessionKey);
    }

    /**
     * Get the authenticated User model instance, or null if not found.
     *
     * @return User|null
     */
    public function user(): null|User
    {
        $id = $this->id();
        return $id ? User::find($id) : null;
    }

    /**
     * Get the authenticated User model instance or throw exception if not found.
     *
     * @throws ModelNotFoundException
     * @return User
     */
    public function userOrFail(): User
    {
        $id = $this->id();

        if (!$id || !$user = User::find($id)) {
            throw new ModelNotFoundException("User not found");
        }

        return $user;
    }

    /**
     * Check if the authenticated user is allowed to perform the given action(s).
     *
     * @param string|array $actions The action or list of actions to validate.
     * @return bool True if the user has permission for at least one of the actions; otherwise, false.
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
     * Check if the authenticated user has any of the given roles.
     *
     * @param string|array $roles
     * @return bool
     */
    public function hasRole(string|array $roles): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }

        if ($user->hasRoles('root')) {
            return true;
        }

        return $user->hasRoles($roles);
    }
}
