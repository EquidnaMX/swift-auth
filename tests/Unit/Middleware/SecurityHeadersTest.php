<?php

/**
 * Unit tests for SecurityHeaders middleware.
 */

namespace Equidna\SwiftAuth\Tests\Unit\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use PHPUnit\Framework\TestCase;

use Equidna\SwiftAuth\Http\Middleware\SecurityHeaders;

final class SecurityHeadersTest extends TestCase
{
    public function test_it_sets_expected_security_headers(): void
    {
        $mw = new SecurityHeaders();
        $request = Request::create('/');

        $next = function ($req) {
            return new Response('OK', 200);
        };

        /** @var Response $response */
        $response = $mw->handle($request, $next);

        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    public function test_it_sets_hsts_when_secure_request(): void
    {
        $mw = new SecurityHeaders();
        $request = Request::create('https://example.com/');

        $next = function ($req) {
            return new Response('OK', 200);
        };

        /** @var Response $response */
        $response = $mw->handle($request, $next);

        $this->assertSame('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }
}
