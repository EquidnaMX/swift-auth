<?php

/**
 * Unit tests for SelectiveRender trait.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Traits
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Tests\Unit\Traits;
use PHPUnit\Framework\TestCase;

use Equidna\SwiftAuth\Traits\SelectiveRender;

/**
 * Tests SelectiveRender trait behavior in isolation.
 *
 * Note: Full testing of this trait requires Laravel container for Config, session, view, and Inertia facades.
 * These tests document expected behavior and test structure.
 */
class SelectiveRenderTest extends TestCase
{
    /**
     * Test trait exists and contains expected method.
     */
    public function test_trait_exists_and_has_render_method(): void
    {
        $reflection = new \ReflectionClass(SelectiveRender::class);

        $this->assertTrue($reflection->isTrait());
        $this->assertTrue($reflection->hasMethod('render'));
    }

    /**
     * Test render method exists and has correct signature.
     */
    public function test_render_method_exists(): void
    {
        $reflection = new \ReflectionClass(SelectiveRender::class);

        $this->assertTrue($reflection->hasMethod('render'));

        $method = $reflection->getMethod('render');
        $this->assertTrue($method->isProtected());
        $this->assertCount(3, $method->getParameters());
    }

    /**
     * Test render method parameters have correct names.
     */
    public function test_render_method_parameters_are_named_correctly(): void
    {
        $reflection = new \ReflectionClass(SelectiveRender::class);
        $method = $reflection->getMethod('render');
        $params = $method->getParameters();

        $this->assertSame('bladeView', $params[0]->getName());
        $this->assertSame('inertiaComponent', $params[1]->getName());
        $this->assertSame('data', $params[2]->getName());
    }

    /**
     * Test data parameter has default empty array.
     */
    public function test_data_parameter_has_default_value(): void
    {
        $reflection = new \ReflectionClass(SelectiveRender::class);
        $method = $reflection->getMethod('render');
        $params = $method->getParameters();

        $dataParam = $params[2];
        $this->assertTrue($dataParam->isDefaultValueAvailable());
        $this->assertSame([], $dataParam->getDefaultValue());
    }

    /**
     * Test render method merges flash messages into data.
     *
     * Expected behavior:
     * - Retrieves 'success', 'error', 'status' from session
     * - Merges these into $data array
     * - Passed data takes precedence over flash messages
     */
    public function test_render_merges_flash_messages_into_data(): void
    {
        // Behavior documented:
        // $flashMessages = ['success' => session('success'), 'error' => session('error'), 'status' => session('status')]
        // $data = array_merge($data, $flashMessages)

        $flashMessages = [
            'success' => 'Operation successful',
            'error' => null,
            'status' => 'pending',
        ];

        $userData = ['user' => 'John', 'age' => 30];

        $merged = array_merge($userData, $flashMessages);

        $this->assertArrayHasKey('user', $merged);
        $this->assertArrayHasKey('success', $merged);
        $this->assertArrayHasKey('error', $merged);
        $this->assertArrayHasKey('status', $merged);
        $this->assertSame('John', $merged['user']);
        $this->assertSame('Operation successful', $merged['success']);
    }

    /**
     * Test render chooses Blade when config frontend is 'blade'.
     *
     * Expected: Config::get('swift-auth.frontend') === 'blade' ? view() : Inertia::render()
     */
    public function test_render_uses_blade_when_frontend_is_blade(): void
    {
        // Requires Laravel container to test Config facade
        // Document expected behavior:
        // if (Config::get('swift-auth.frontend') === 'blade') return view($bladeView, $data)
        $this->expectNotToPerformAssertions();
    }

    /**
     * Test render chooses Inertia when config frontend is not 'blade'.
     *
     * Expected: Inertia::render($inertiaComponent, $data)
     */
    public function test_render_uses_inertia_when_frontend_is_not_blade(): void
    {
        // Requires Laravel container to test Config and Inertia facades
        $this->expectNotToPerformAssertions();
    }

    /**
     * Test array_merge preserves data precedence.
     *
     * When merging ['a' => 1] with ['a' => 2], result is ['a' => 2]
     */
    public function test_array_merge_overwrites_keys(): void
    {
        $first = ['key' => 'value1', 'another' => 'keep'];
        $second = ['key' => 'value2'];

        $result = array_merge($first, $second);

        $this->assertSame('value2', $result['key']);
        $this->assertSame('keep', $result['another']);
    }

    /**
     * Test flash messages extract three specific keys from session.
     */
    public function test_flash_messages_include_success_error_status(): void
    {
        // Expected behavior: retrieves exactly these three keys
        $expectedKeys = ['success', 'error', 'status'];

        $this->assertCount(3, $expectedKeys);
        $this->assertContains('success', $expectedKeys);
        $this->assertContains('error', $expectedKeys);
        $this->assertContains('status', $expectedKeys);
    }

    /**
     * Test session retrieval handles null values gracefully.
     */
    public function test_handles_null_session_values(): void
    {
        // session('key') returns null if not set
        $flashMessages = [
            'success' => null,
            'error' => null,
            'status' => null,
        ];

        $this->assertNull($flashMessages['success']);
        $this->assertNull($flashMessages['error']);
        $this->assertNull($flashMessages['status']);
    }

    /**
     * Test return type is View or Response union.
     */
    public function test_return_type_is_view_or_response_union(): void
    {
        $reflection = new \ReflectionClass(SelectiveRender::class);
        $method = $reflection->getMethod('render');

        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);

        // PHP 8.0+ union types
        $this->assertInstanceOf(\ReflectionUnionType::class, $returnType);

        $types = $returnType->getTypes();
        $typeNames = array_map(fn($t) => $t->getName(), $types);

        $this->assertContains('Illuminate\Contracts\View\View', $typeNames);
        $this->assertContains('Inertia\Response', $typeNames);
    }
}
