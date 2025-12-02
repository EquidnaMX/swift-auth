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

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Session\Store as Session;
use SessionHandlerInterface;

use Equidna\SwiftAuth\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Events\MfaChallengeStarted;
use Equidna\SwiftAuth\Events\SessionEvicted;
use Equidna\SwiftAuth\Events\UserLoggedIn;
use Equidna\SwiftAuth\Events\UserLoggedOut;
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
     * @param  Dispatcher               $events          Event dispatcher instance.
     */
    public function __construct(
        Session $session,
        private UserRepositoryInterface $userRepository,
        private Dispatcher $events
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

        $this->dispatchEvent(new UserLoggedIn(
            $user->getKey(),
            $this->getSessionId(),
            $this->getClientIp(),
            $this->getDriverMetadata()
        ));
    }

    /**
     * Logs out the user by removing their ID from the session.
     *
     * @return void
     */
    public function logout(): void
    {
        $userId = $this->id();
        $sessionId = $this->getSessionId();

        $this->session->forget($this->sessionKey);

        $this->dispatchEvent(new UserLoggedOut(
            $userId,
            $sessionId,
            $this->getClientIp(),
            $this->getDriverMetadata()
        ));
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

    /**
     * Dispatches a session eviction event when a session limit is enforced.
     *
     * @param  User   $user             User whose sessions are being limited.
     * @param  string $evictedSessionId Identifier of the evicted session.
     * @return void
     */
    public function enforceSessionLimit(User $user, string $evictedSessionId): void
    {
        $this->dispatchEvent(new SessionEvicted(
            $user->getKey(),
            $evictedSessionId,
            $this->getClientIp(),
            $this->getDriverMetadata()
        ));
    }

    /**
     * Dispatches an event indicating a multi-factor authentication challenge has started.
     *
     * @param  User $user  User undergoing MFA challenge.
     * @return void
     */
    public function startMfaChallenge(User $user): void
    {
        $this->dispatchEvent(new MfaChallengeStarted(
            $user->getKey(),
            $this->getSessionId(),
            $this->getClientIp(),
            $this->getDriverMetadata()
        ));
    }

    /**
     * Dispatches events via the configured dispatcher.
     *
     * @param  object $event  Event instance to dispatch.
     * @return void
     */
    private function dispatchEvent(object $event): void
    {
        $this->events->dispatch($event);
    }

    /**
     * Returns metadata about the session storage driver and handler.
     *
     * @return array<string, string>
     */
    private function getDriverMetadata(): array
    {
        $handler = $this->session->getHandler();

        return [
            'handler' => $handler instanceof SessionHandlerInterface ? SessionHandlerInterface::class : (string) $handler,
            'name' => $this->session->getName(),
            'store' => $this->session::class,
        ];
    }

    /**
     * Returns the current session identifier.
     *
     * @return string
     */
    private function getSessionId(): string
    {
        return (string) $this->session->getId();
    }

    /**
     * Attempts to determine the client's IP address.
     *
     * @return string|null
     */
    private function getClientIp(): ?string
    {
        if (function_exists('request') && request()) {
            return request()->ip();
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
}
