<?php

/**
 * Adds security headers to HTTP responses.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Http\Middleware
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Http\Middleware;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Closure;

/**
 * Enforces security-focused HTTP headers on all responses.
 *
 * Mitigates clickjacking, XSS, MIME sniffing, and other common web attacks
 * by setting recommended security headers.
 */
final class SecurityHeaders
{
    /**
     * Applies security headers to the HTTP response.
     *
     * @param  Request  $request  Incoming HTTP request.
     * @param  Closure  $next     Next middleware in the pipeline.
     * @return Response           Response with security headers applied.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Control referrer information leakage
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Enable XSS protection in older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Enforce HTTPS if request is secure
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Content Security Policy and Permissions Policy (if config is available)
        $container = Container::getInstance();
        $hasConfig = $container !== null && $container->has('config');

        if ($hasConfig) {
            $csp = config('swift-auth.security_headers.csp');
            if ($csp) {
                $response->headers->set('Content-Security-Policy', $csp);
            }

            $permissionsPolicy = config('swift-auth.security_headers.permissions_policy');
            if ($permissionsPolicy) {
                $response->headers->set('Permissions-Policy', $permissionsPolicy);
            }
        }

        return $response;
    }
}
