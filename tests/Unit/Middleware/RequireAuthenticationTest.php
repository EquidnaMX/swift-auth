<?php

/**
 * Unit tests for RequireAuthentication middleware.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Middleware
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Tests\Unit\Middleware;

use Equidna\SwiftAuth\Http\Middleware\RequireAuthentication;
use Equidna\SwiftAuth\Facades\SwiftAuth;
use Equidna\SwiftAuth\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests RequireAuthentication middleware in isolation.
 */
class RequireAuthenticationTest extends TestCase
{
    private RequireAuthentication $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RequireAuthentication();
    }

    /**
     * Test middleware passes request when user is authenticated.
     *
     * Note: This test demonstrates structure but would need Laravel test case
     * for full facade mocking. Consider this a template for feature tests.
     */
    public function test_middleware_structure_is_correct(): void
    {
        $this->assertInstanceOf(RequireAuthentication::class, $this->middleware);
    }

    /**
     * Test middleware has handle method with correct signature.
     */
    public function test_middleware_has_handle_method(): void
    {
        $this->assertTrue(
            method_exists($this->middleware, 'handle'),
            'Middleware must have handle method'
        );

        $reflection = new \ReflectionMethod($this->middleware, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('request', $parameters[0]->getName());
        $this->assertEquals('next', $parameters[1]->getName());
    }

    /**
     * Test handle method returns Response.
     */
    public function test_handle_method_returns_response(): void
    {
        $reflection = new \ReflectionMethod($this->middleware, 'handle');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals(Response::class, $returnType->getName());
    }
}
