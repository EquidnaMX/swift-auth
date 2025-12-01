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
 * @method static void login(User $user)
 * @method static void logout()
 * @method static bool check()
 * @method static int|null id()
 * @method static User|null user()
 * @method static bool canPerformAction(string|array<string,mixed> $actions)
 * @method static User userOrFail()
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
