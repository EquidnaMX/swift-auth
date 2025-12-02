<?php

/**
 * Test helper utilities for SwiftAuth testing.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Tests;

use Equidna\SwiftAuth\Models\User;
use Equidna\SwiftAuth\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

/**
 * Provides helper methods for creating test data and assertions.
 *
 * Usage in tests:
 * ```php
 * use Equidna\SwiftAuth\Tests\TestHelpers;
 *
 * class MyFeatureTest extends TestCase
 * {
 *     use TestHelpers;
 *
 *     public function test_example()
 *     {
 *         $user = $this->createTestUser(['email' => 'test@example.com']);
 *         // ...
 *     }
 * }
 * ```
 */
trait TestHelpers
{
    /**
     * Create a test user with optional attributes.
     *
     * @param  array<string, mixed> $attributes User attributes to override.
     * @return User                             Created user instance.
     */
    protected function createTestUser(array $attributes = []): User
    {
        $defaults = [
            'name' => 'Test User',
            'email' => 'test' . Str::random(8) . '@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ];

        return User::create(array_merge($defaults, $attributes));
    }

    /**
     * Create a test role with optional attributes.
     *
     * @param  array<string, mixed> $attributes Role attributes to override.
     * @return Role                             Created role instance.
     */
    protected function createTestRole(array $attributes = []): Role
    {
        $defaults = [
            'name' => 'test-role-' . Str::random(8),
            'description' => 'Test role description',
            'actions' => ['users.view', 'posts.view'],
        ];

        return Role::create(array_merge($defaults, $attributes));
    }

    /**
     * Create a test user with specific roles.
     *
     * @param  array<int, string>   $roleNames Array of role names to assign.
     * @param  array<string, mixed> $userAttributes User attributes.
     * @return User                                User with assigned roles.
     */
    protected function createTestUserWithRoles(
        array $roleNames,
        array $userAttributes = []
    ): User {
        $user = $this->createTestUser($userAttributes);

        foreach ($roleNames as $roleName) {
            $role = Role::where('name', $roleName)->first()
                ?? $this->createTestRole(['name' => $roleName]);
            $user->roles()->attach($role->getKey());
        }

        return $user->fresh(['roles']);
    }

    /**
     * Create an admin test user with full permissions.
     *
     * @param  array<string, mixed> $attributes User attributes.
     * @return User                             Admin user instance.
     */
    protected function createAdminUser(array $attributes = []): User
    {
        $adminRole = Role::where('name', 'admin')->first()
            ?? $this->createTestRole([
                'name' => 'admin',
                'description' => 'Administrator',
                'actions' => ['sw-admin'],
            ]);

        $user = $this->createTestUser(array_merge([
            'name' => 'Admin User',
            'email' => 'admin' . Str::random(8) . '@example.com',
        ], $attributes));

        $user->roles()->attach($adminRole->getKey());

        return $user->fresh(['roles']);
    }

    /**
     * Create a locked user account for testing lockout scenarios.
     *
     * @param  int                  $minutesLocked Minutes until unlock.
     * @param  array<string, mixed> $attributes User attributes.
     * @return User                            Locked user instance.
     */
    protected function createLockedUser(
        int $minutesLocked = 15,
        array $attributes = []
    ): User {
        return $this->createTestUser(array_merge([
            'failed_login_attempts' => 5,
            'locked_until' => now()->addMinutes($minutesLocked),
            'last_failed_login_at' => now(),
        ], $attributes));
    }

    /**
     * Create a user with unverified email for testing verification flow.
     *
     * @param  array<string, mixed> $attributes User attributes.
     * @return User                             User with unverified email.
     */
    protected function createUnverifiedUser(array $attributes = []): User
    {
        return $this->createTestUser(array_merge([
            'email_verified_at' => null,
            'email_verification_token' => hash('sha256', Str::random(64)),
            'email_verification_sent_at' => now(),
        ], $attributes));
    }

    /**
     * Assert that a user has a specific role.
     *
     * @param  User   $user     User instance.
     * @param  string $roleName Role name to check.
     * @return void
     */
    protected function assertUserHasRole(
        User $user,
        string $roleName,
    ): void
    {
        $this->assertTrue(
            $user->fresh(['roles'])->hasRoles($roleName),
            "User does not have role: {$roleName}"
        );
    }

    /**
     * Assert that a user does not have a specific role.
     *
     * @param  User   $user     User instance.
     * @param  string $roleName Role name to check.
     * @return void
     */
    protected function assertUserDoesNotHaveRole(
        User $user,
        string $roleName,
    ): void
    {
        $this->assertFalse(
            $user->fresh(['roles'])->hasRoles($roleName),
            "User has role: {$roleName}"
        );
    }

    /**
     * Assert that a user can perform a specific action.
     *
     * @param  User   $user   User instance.
     * @param  string $action Action to check (e.g., 'users.create').
     * @return void
     */
    protected function assertUserCanPerformAction(
        User $user,
        string $action,
    ): void
    {
        $actions = $user->fresh(['roles'])->availableActions();
        $this->assertContains(
            $action,
            $actions,
            "User cannot perform action: {$action}"
        );
    }

    /**
     * Assert that a user account is locked.
     *
     * @param  User $user User instance.
     * @return void
     */
    protected function assertUserIsLocked(User $user): void
    {
        $user = $user->fresh();
        $this->assertNotNull($user->locked_until, 'User is not locked');
        $this->assertTrue(
            $user->locked_until->isFuture(),
            'User lock has expired'
        );
    }

    /**
     * Assert that a user account is not locked.
     *
     * @param  User $user User instance.
     * @return void
     */
    protected function assertUserIsNotLocked(User $user): void
    {
        $user = $user->fresh();
        $this->assertTrue(
            $user->locked_until === null || $user->locked_until->isPast(),
            'User is still locked'
        );
    }

    /**
     * Assert that an email verification token exists for a user.
     *
     * @param  User $user User instance.
     * @return void
     */
    protected function assertUserHasVerificationToken(User $user): void
    {
        $user = $user->fresh();
        $this->assertNotNull(
            $user->email_verification_token,
            'User does not have verification token'
        );
    }

    /**
     * Generate a valid password reset token for testing.
     *
     * @return string Raw token (before hashing).
     */
    protected function generatePasswordResetToken(): string
    {
        return Str::random(64);
    }

    /**
     * Generate a valid email verification token for testing.
     *
     * @return string Raw token (before hashing).
     */
    protected function generateEmailVerificationToken(): string
    {
        return Str::random(64);
    }

    /**
     * Simulate failed login attempts for a user.
     *
     * @param  User $user     User instance.
     * @param  int  $attempts Number of failed attempts.
     * @return User           Updated user instance.
     */
    protected function simulateFailedLoginAttempts(
        User $user,
        int $attempts,
    ): User
    {
        $user->failed_login_attempts = $attempts;
        $user->last_failed_login_at = now();
        $user->save();

        return $user->fresh();
    }
}
