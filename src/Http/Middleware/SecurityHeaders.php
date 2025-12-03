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
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        /** @var Response $response */
        $response = $next($request);

        // Prevent clickjacking attacks
        $response->headers->set(
            'X-Frame-Options',
            'SAMEORIGIN',
        );

        // Prevent MIME type sniffing
        $response->headers->set(
            'X-Content-Type-Options',
            'nosniff',
        );

        // Control referrer information leakage
        $response->headers->set(
            'Referrer-Policy',
            (string) config('swift-auth.security_headers.referrer_policy', 'strict-origin-when-cross-origin'),
        );

        // Enable XSS protection in older browsers
        $response->headers->set(
            'X-XSS-Protection',
            '1; mode=block',
        );

        // Enforce HTTPS if request is secure
        $hstsConfig = config('swift-auth.security_headers.hsts', []);
        if ($request->isSecure() && ($hstsConfig['enabled'] ?? true)) {
            $hstsMaxAge = (int) ($hstsConfig['max_age'] ?? 31536000);
            $hstsParts = ["max-age={$hstsMaxAge}"];

            if ($hstsConfig['include_subdomains'] ?? true) {
                $hstsParts[] = 'includeSubDomains';
            }

            if ($hstsConfig['preload'] ?? false) {
                $hstsParts[] = 'preload';
            }

            $response->headers->set(
                'Strict-Transport-Security',
                implode('; ', $hstsParts),
            );
        }

        // Content Security Policy and Permissions Policy (if config is available)
        $container = Container::getInstance();
        $hasConfig = $container !== null && $container->has('config');

        if ($hasConfig) {
            $csp = config('swift-auth.security_headers.csp');
            if ($csp) {
                $response->headers->set(
                    'Content-Security-Policy',
                    $csp,
                );
            }

            $permissionsPolicy = config('swift-auth.security_headers.permissions_policy');
            if ($permissionsPolicy) {
                $response->headers->set(
                    'Permissions-Policy',
                    $permissionsPolicy,
                );
            }

            $coop = config('swift-auth.security_headers.cross_origin_opener_policy');
            if ($coop) {
                $response->headers->set(
                    'Cross-Origin-Opener-Policy',
                    $coop,
                );
            }

            $coep = config('swift-auth.security_headers.cross_origin_embedder_policy');
            if ($coep) {
                $response->headers->set(
                    'Cross-Origin-Embedder-Policy',
                    $coep,
                );
            }

            $corp = config('swift-auth.security_headers.cross_origin_resource_policy');
            if ($corp) {
                $response->headers->set(
                    'Cross-Origin-Resource-Policy',
                    $corp,
                );
            }
        }

        return $response;
    }
}
