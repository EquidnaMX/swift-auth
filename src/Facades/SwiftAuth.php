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

namespace Equidna\SwiftAuth\Facades;

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
 * @see \Equidna\SwiftAuth\Services\SwiftSessionAuth
 *
 * @method static array login(User $user, null|string $ipAddress = null, null|string $userAgent = null, null|string $deviceName = null, bool $remember = false)
 * @method static void logout()
 * @method static bool check()
 * @method static int|null id()
 * @method static User|null user()
 * @method static bool canPerformAction(string|array<string,mixed> $actions)
 * @method static User userOrFail()
 * @method static \Illuminate\Support\Collection<int, \Equidna\SwiftAuth\Models\UserSession> sessionsForUser(int $userId)
 * @method static void revokeSession(int $userId, string $sessionId)
 * @method static void startMfaChallenge(User $user, string $driver, null|string $ipAddress = null, null|string $userAgent = null)
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
