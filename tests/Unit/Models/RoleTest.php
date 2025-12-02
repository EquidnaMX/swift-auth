<?php

/**
 * Unit tests for Role model business logic.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Models
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;

use Equidna\SwiftAuth\Models\Role;
use Equidna\SwiftAuth\Models\User;

use ReflectionClass;

/**
 * Tests Role model behavior and business rules.
 */
final class RoleTest extends TestCase
{
    /**
     * Test that Role casts actions to array.
     */
    public function test_actions_are_cast_to_array(): void
    {
        $role = new Role();
        $reflection = new ReflectionClass($role);
        $property = $reflection->getProperty('casts');
        $property->setAccessible(true);
        $casts = $property->getValue($role);

        $this->assertArrayHasKey('actions', $casts);
        $this->assertSame('array', $casts['actions']);
    }

    /**
     * Test that Role fillable includes expected fields.
     */
    public function test_fillable_includes_core_fields(): void
    {
        $role = new Role();
        $reflection = new ReflectionClass($role);
        $property = $reflection->getProperty('fillable');
        $property->setAccessible(true);
        $fillable = $property->getValue($role);

        $this->assertContains('name', $fillable);
        $this->assertContains('description', $fillable);
        $this->assertContains('actions', $fillable);
    }

    /**
     * Test that scopeSearch filters by name.
     */
    public function test_scope_search_applies_name_filter(): void
    {
        $role = new Role();
        $mockBuilder = $this->createMock(\Illuminate\Database\Eloquent\Builder::class);

        $mockBuilder->expects($this->once())
            ->method('where')
            ->with('name', 'like', '%admin%')
            ->willReturnSelf();

        $role->scopeSearch($mockBuilder, 'admin');
    }

    /**
     * Test that scopeSearch returns builder unchanged when search is empty.
     */
    public function test_scope_search_returns_unchanged_when_empty(): void
    {
        $role = new Role();
        $mockBuilder = $this->createMock(\Illuminate\Database\Eloquent\Builder::class);

        $mockBuilder->expects($this->never())
            ->method('where');

        $result = $role->scopeSearch($mockBuilder, '');
        $this->assertSame($mockBuilder, $result);
    }

    /**
     * Test that users relationship returns BelongsToMany.
     */
    public function test_users_relationship_is_belongs_to_many(): void
    {
        $role = new Role();
        $relation = $role->users();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $relation);
        $this->assertSame(User::class, $relation->getRelated()::class);
    }

    /**
     * Test that actions can store empty array.
     */
    public function test_actions_can_be_empty_array(): void
    {
        $role = new Role(['actions' => []]);
        $this->assertSame([], $role->actions);
    }

    /**
     * Test that actions can store multiple strings.
     */
    public function test_actions_can_store_multiple_strings(): void
    {
        $actions = ['create-user', 'delete-user', 'sw-admin'];
        $role = new Role(['actions' => $actions]);

        $this->assertSame($actions, $role->actions);
        $this->assertIsArray($role->actions);
        $this->assertCount(3, $role->actions);
    }

    /**
     * Test that Role table name uses config prefix.
     */
    public function test_table_name_uses_config_prefix(): void
    {
        $role = new Role();
        $expectedPrefix = config('swift-auth.table_prefix', 'swift_');
        $expectedTable = $expectedPrefix . 'Roles';

        $this->assertSame($expectedTable, $role->getTable());
    }

    /**
     * Test that Role has timestamps enabled.
     */
    public function test_has_timestamps_enabled(): void
    {
        $role = new Role();
        $this->assertTrue($role->timestamps);
    }

    /**
     * Test that name field is required for mass assignment.
     */
    public function test_name_is_fillable(): void
    {
        $role = new Role(['name' => 'Test Role']);
        $this->assertSame('Test Role', $role->name);
    }

    /**
     * Test that description is fillable.
     */
    public function test_description_is_fillable(): void
    {
        $role = new Role(['description' => 'Test description']);
        $this->assertSame('Test description', $role->description);
    }

    /**
     * Test that actions with special characters are preserved.
     */
    public function test_actions_preserve_special_characters(): void
    {
        $actions = ['user:create', 'user:delete', 'admin:*'];
        $role = new Role(['actions' => $actions]);

        $this->assertSame($actions, $role->actions);
    }

    /**
     * Test that actions with unicode are preserved.
     */
    public function test_actions_preserve_unicode(): void
    {
        $actions = ['créer-utilisateur', '削除ユーザー', 'создать-пользователя'];
        $role = new Role(['actions' => $actions]);

        $this->assertSame($actions, $role->actions);
    }
}
