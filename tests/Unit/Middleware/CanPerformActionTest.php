<?php

/**
 * Unit tests for CanPerformAction middleware.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Middleware
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Tests\Unit\Middleware;

use Equidna\SwiftAuth\Http\Middleware\CanPerformAction;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests CanPerformAction middleware structure and signature.
 */
class CanPerformActionTest extends TestCase
{
    private CanPerformAction $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CanPerformAction();
    }

    /**
     * Test middleware instance creation.
     */
    public function test_middleware_can_be_instantiated(): void
    {
        $this->assertInstanceOf(CanPerformAction::class, $this->middleware);
    }

    /**
     * Test middleware has handle method with correct signature.
     */
    public function test_middleware_has_handle_method_with_action_parameter(): void
    {
        $this->assertTrue(
            method_exists($this->middleware, 'handle'),
            'Middleware must have handle method'
        );

        $reflection = new \ReflectionMethod($this->middleware, 'handle');
        $parameters = $reflection->getParameters();

        $this->assertCount(3, $parameters);
        $this->assertEquals('request', $parameters[0]->getName());
        $this->assertEquals('next', $parameters[1]->getName());
        $this->assertEquals('action', $parameters[2]->getName());
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

    /**
     * Test action parameter is string type.
     */
    public function test_action_parameter_is_string_type(): void
    {
        $reflection = new \ReflectionMethod($this->middleware, 'handle');
        $parameters = $reflection->getParameters();
        $actionParam = $parameters[2];

        $this->assertEquals('string', $actionParam->getType()->getName());
    }
}
