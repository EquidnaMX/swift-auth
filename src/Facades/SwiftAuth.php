<?php

namespace Equidna\SwiftAuth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class SwiftAuth
 *
 * This is the facade for the SwiftSessionAuth service.
 * It provides an easy static interface to access session-based authentication logic
 * registered as 'swift-auth' in the service container.
 *
 * Example usage:
 * ```php
 * SwiftAuth::isAuthenticated();
 * SwiftAuth::user();
 * ```
 *
 * @see \Equidna\SwiftAuth\Services\SwiftSessionAuth
 *
 * @method static bool login(array $credentials)
 * @method static void logout()
 * @method static bool check()
 * @method static int|null id()
 * @method static mixed user()
 * @method static bool canPerformAction(string $action)
 * @method static mixed userOrFail()
 */
class SwiftAuth extends Facade
{
    /**
     * Get the registered name of the component in the container.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'swift-auth';
    }
}
