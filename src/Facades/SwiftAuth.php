<?php

namespace Equidna\SwifthAuth\Facades;

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
 * @see \Equidna\SwifthAuth\Services\SwiftSessionAuth
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
