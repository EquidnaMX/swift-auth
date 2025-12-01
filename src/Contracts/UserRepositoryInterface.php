<?php

/**
 * Defines user persistence operations for SwiftAuth.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Contracts
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Contracts;
use Equidna\SwiftAuth\Models\User;

/**
 * Abstracts user data access for authentication flows.
 *
 * Enables dependency injection and simplifies unit testing by decoupling
 * authentication logic from Eloquent's static methods.
 */
interface UserRepositoryInterface
{
    /**
     * Finds a user by their primary key.
     *
     * @param  int|string $id  User primary key.
     * @return User|null       User instance or null if not found.
     */
    public function findById(int|string $id): ?User;

    /**
     * Finds a user by their email address.
     *
     * @param  string    $email  Email address (case-insensitive).
     * @return User|null         User instance or null if not found.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Increments the failed login attempt counter for a user.
     *
     * Updates failed_login_attempts and last_failed_login_at timestamp.
     *
     * @param  User $user  User instance to update.
     * @return void
     */
    public function incrementFailedLogins(User $user): void;

    /**
     * Resets failed login attempts and clears lockout state.
     *
     * Sets failed_login_attempts to 0, clears locked_until and last_failed_login_at.
     *
     * @param  User $user  User instance to reset.
     * @return void
     */
    public function resetFailedLogins(User $user): void;

    /**
     * Locks a user account for a specified duration.
     *
     * Sets locked_until timestamp and saves the user.
     *
     * @param  User $user     User instance to lock.
     * @param  int  $seconds  Lockout duration in seconds.
     * @return void
     */
    public function lockAccount(User $user, int $seconds): void;
}
