<?php

/**
 * Facade for the SwiftSessionAuth service.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Facades
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth Package repository
 */

namespace Equidna\SwiftAuth\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Equidna\SwiftAuth\Models\User;

/**
 * Provides a static interface to the SwiftSessionAuth service.
 *
 * Simplifies access to session-based authentication logic registered as 'swift-auth'
 * in the service container.
 *
 * Example usage:
 * ```php
 * SwiftAuth::isAuthenticated();
 * SwiftAuth::user();
 * ```
 *
 * @see \Equidna\SwiftAuth\Classes\Auth\SwiftSessionAuth
 *
 * @method static array{evicted_session_ids: array<int, string>} login(User $user, null|string $ipAddress = null, null|string $userAgent = null, null|string $deviceName = null, bool $remember = false)
 * @method static void logout()
 * @method static bool check()
 * @method static int|null id()
 * @method static User|null user()
 * @method static bool canPerformAction(string|array<string,mixed> $actions)
 * @method static bool hasRole(string|array<string> $roles)
 * @method static User userOrFail()
 * @method static \Illuminate\Support\Collection<int, \Equidna\SwiftAuth\Models\UserSession> sessionsForUser(int $userId)
 * @method static void revokeSession(int $userId, string $sessionId)
 * @method static array<int, string> enforceSessionLimit(\Equidna\SwiftAuth\Models\User $user, string $currentSessionId)
 * @method static void startMfaChallenge(\Equidna\SwiftAuth\Models\User $user, string $driver = 'otp', null|string $ipAddress = null, null|string $userAgent = null)
 */
class SwiftAuth extends Facade
{
    /**
     * Returns the registered name of the component in the container.
     *
     * @return string  Service container binding key.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'swift-auth';
    }
}
