<?php

/**
 * Provides reusable rate limiting methods for controllers.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Traits
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth Package repository
 */

namespace Equidna\SwiftAuth\Classes\Auth\Traits;

use Illuminate\Support\Facades\RateLimiter;
use Equidna\Toolkit\Exceptions\UnauthorizedException;

/**
 * Consolidates rate limiting logic for authentication flows.
 *
 * Reduces duplication across controllers that enforce attempt throttling.
 */
trait ChecksRateLimits
{
    /**
     * Checks if the rate limit has been exceeded for a given key.
     *
     * @param  string $key       Rate limiter key.
     * @param  int    $attempts  Maximum allowed attempts.
     * @param  string $message   Error message to throw when limit exceeded.
     * @return void
     * @throws UnauthorizedException  When rate limit is exceeded.
     */
    protected function checkRateLimit(
        string $key,
        int $attempts,
        string $message = 'Too many attempts. Please try again later.'
    ): void {
        if (RateLimiter::tooManyAttempts($key, $attempts)) {
            $availableIn = RateLimiter::availableIn($key);
            throw new UnauthorizedException(
                $message . ' Please try again in ' . $availableIn . ' seconds.'
            );
        }
    }

    /**
     * Records a rate limit hit for the given key.
     *
     * @param  string $key          Rate limiter key.
     * @param  int    $decaySeconds Decay duration in seconds.
     * @return void
     */
    protected function hitRateLimit(string $key, int $decaySeconds): void
    {
        RateLimiter::hit($key, $decaySeconds);
    }

    /**
     * Clears the rate limiter for the given key.
     *
     * @param  string $key  Rate limiter key.
     * @return void
     */
    protected function clearRateLimit(string $key): void
    {
        RateLimiter::clear($key);
    }

    /**
     * Returns the remaining time in seconds before the rate limit resets.
     *
     * @param  string $key  Rate limiter key.
     * @return int          Seconds until the rate limit resets.
     */
    protected function rateLimitAvailableIn(string $key): int
    {
        return RateLimiter::availableIn($key);
    }
}
