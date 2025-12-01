<?php

/**
 * Rate limiter configuration for SwiftAuth routes.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Providers
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Providers;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Configures rate limiting for public SwiftAuth endpoints.
 *
 * Throttles registration, password reset requests, and email verification resends
 * based on IP address and email to prevent abuse.
 */
final class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Bootstraps rate limiters for SwiftAuth routes.
     *
     * @return void
     */
    public function boot(): void
    {
        RateLimiter::for(
            'swift-auth-registration',
            function (Request $request) {
                return Limit::perMinute(5)
                    ->by($request->ip())
                    ->response(
                        function (Request $request, array $headers) {
                            return response()->json(
                                ['message' => 'Too many registration attempts. Please try again later.'],
                                429,
                                $headers
                            );
                        }
                    );
            }
        );

        RateLimiter::for(
            'swift-auth-password-reset',
            function (Request $request) {
                $attempts = config('swift-auth.password_reset_rate_limit.attempts', 5);
                $decaySeconds = config('swift-auth.password_reset_rate_limit.decay_seconds', 60);

                return Limit::perMinutes($decaySeconds / 60, $attempts)
                    ->by($request->input('email', $request->ip()))
                    ->response(
                        function (Request $request, array $headers) {
                            return response()->json(
                                ['message' => 'Too many password reset requests. Please wait before trying again.'],
                                429,
                                $headers
                            );
                        }
                    );
            }
        );

        RateLimiter::for(
            'swift-auth-email-verification',
            function (Request $request) {
                $attempts = config('swift-auth.email_verification.resend_rate_limit.attempts', 3);
                $decaySeconds = config('swift-auth.email_verification.resend_rate_limit.decay_seconds', 300);

                return Limit::perMinutes($decaySeconds / 60, $attempts)
                    ->by($request->input('email', $request->ip()))
                    ->response(
                        function (Request $request, array $headers) {
                            return response()->json(
                                ['message' => 'Too many email verification requests. Please check your inbox or try again later.'],
                                429,
                                $headers
                            );
                        }
                    );
            }
        );
    }
}
