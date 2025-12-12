<?php

/**
 * Unit tests for User model.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Models
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Equidna\SwiftAuth\Tests\TestCase;
use Equidna\SwiftAuth\Models\Role;
use Equidna\SwiftAuth\Models\User;

/**
 * Tests User model business logic with database.
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Tests hasRoles returns true when user has the specified role.
     */
    public function test_has_roles_returns_true_when_user_has_role(): void
    {
        $user = $this->createUserWithRoles(['admin', 'editor']);

        $this->assertTrue($user->hasRoles('admin'));
        $this->assertTrue($user->hasRoles(['admin']));
        $this->assertTrue($user->hasRoles(['admin', 'viewer'])); // Has at least one
    }

    /**
     * Tests hasRoles returns false when user does not have the role.
     */
    public function test_has_roles_returns_false_when_user_lacks_role(): void
    {
        $user = $this->createUserWithRoles(['editor']);

        $this->assertFalse($user->hasRoles('admin'));
        $this->assertFalse($user->hasRoles(['admin', 'viewer']));
    }

    /**
     * Tests hasRoles is case-insensitive.
     */
    public function test_has_roles_is_case_insensitive(): void
    {
        $user = $this->createUserWithRoles(['Admin']);

        $this->assertTrue($user->hasRoles('admin'));
        $this->assertTrue($user->hasRoles('ADMIN'));
        $this->assertTrue($user->hasRoles('AdMiN'));
    }

    /**
     * Tests availableActions returns unique actions from all roles.
     */
    public function test_available_actions_returns_unique_actions_from_all_roles(): void
    {
        $user = $this->createUserWithActions([
            ['users.view', 'users.create'],
            ['users.view', 'posts.edit'], // Duplicate users.view
        ]);

        $actions = $user->availableActions();

        $this->assertCount(3, $actions);
        $this->assertContains('users.view', $actions);
        $this->assertContains('users.create', $actions);
        $this->assertContains('posts.edit', $actions);
    }

    /**
     * Tests availableActions returns empty array when user has no roles.
     */
    public function test_available_actions_returns_empty_when_no_roles(): void
    {
        $user = $this->createUserWithRoles([]);

        $this->assertEmpty($user->availableActions());
    }

    /**
     * Tests availableActions returns empty array when roles have no actions.
     */
    public function test_available_actions_returns_empty_when_roles_have_no_actions(): void
    {
        $user = $this->createUserWithActions([[], []]);

        $this->assertEmpty($user->availableActions());
    }

    /**
     * Tests availableActions uses memoization (cached result).
     */
    public function test_available_actions_uses_memoization(): void
    {
        $user = $this->createUserWithActions([['users.view']]);

        // First call should parse actions
        $firstCall = $user->availableActions();

        // Second call should return cached result
        $secondCall = $user->availableActions();

        $this->assertSame($firstCall, $secondCall);
    }

    /**
     * Creates a User instance with roles in the database.
     *
     * @param  array<int, string> $roleNames  Role names to assign.
     * @return User                           User instance with roles.
     */
    private function createUserWithRoles(array $roleNames): User
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'failed_login_attempts' => 0,
        ]);

        foreach ($roleNames as $name) {
            $role = Role::create([
                'name' => $name,
                'actions' => [],
            ]);
            $user->roles()->attach($role);
        }

        return $user->fresh(['roles']);
    }

    /**
     * Creates a User instance with roles that have specific actions.
     *
     * @param  array<int, array<int, string>> $roleActions  Array of action arrays.
     * @return User                                        User instance with roles.
     */
    private function createUserWithActions(array $roleActions): User
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test_' . uniqid() . '@example.com',
            'password' => bcrypt('password'),
            'failed_login_attempts' => 0,
        ]);

        foreach ($roleActions as $actions) {
            $role = Role::create([
                'name' => 'role_' . uniqid(),
                'actions' => $actions,
            ]);
            $user->roles()->attach($role);
        }

        return $user->fresh(['roles']);
    }
}
