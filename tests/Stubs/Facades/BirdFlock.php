<?php

namespace Equidna\BirdFlock\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * BirdFlock Facade Stub for Testing
 * 
 * This is a stub implementation for tests when the actual
 * equidna/bird-flock package is not installed.
 * 
 * @method static void fake()
 * @method static void assertDispatched(callable $callback)  
 * @method static void assertNothingDispatched()
 */
class BirdFlock extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'bird-flock';
    }

    /**
     * Fake the facade for testing.
     */
    public static function fake(): void
    {
        static::swap(new \Equidna\BirdFlock\BirdFlockFake());
    }
}
