<?php

/**
 * Unit tests for AccountLockoutService business logic.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Tests\Unit\Services
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

use Equidna\SwiftAuth\Tests\TestCase;
use Equidna\SwiftAuth\Classes\Auth\Services\AccountLockoutService;
use Equidna\SwiftAuth\Classes\Users\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Classes\Notifications\NotificationService;
use Equidna\SwiftAuth\Classes\Notifications\DTO\NotificationResult;
use Equidna\SwiftAuth\Models\User;

/**
 * Tests AccountLockoutService behavior for failed login tracking and lockout.
 */
class AccountLockoutServiceTest extends TestCase
{

    /**
     * Test that recordFailedAttempt increments failed logins.
     */
    public function test_increments_failed_logins(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $notif = $this->createMock(NotificationService::class);

        $user = new User([]);
        $user->failed_login_attempts = 0;

        $repo->expects($this->once())
            ->method('incrementFailedLogins')
            ->with($this->isInstanceOf(User::class));

        $service = new AccountLockoutService($repo, $notif);
        $service->recordFailedAttempt($user, '127.0.0.1');
    }

    /**
     * Test that resetAttempts resets failed logins.
     */
    public function test_resets_failed_logins(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $notif = $this->createMock(NotificationService::class);

        $user = new User([]);
        $user->failed_login_attempts = 2;

        $repo->expects($this->once())
            ->method('resetFailedLogins')
            ->with($this->isInstanceOf(User::class));

        $service = new AccountLockoutService($repo, $notif);
        $service->resetAttempts($user);
    }

    /**
     * Test that account is locked after max attempts.
     */
    public function test_locks_account_for_duration(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $notif = $this->createMock(NotificationService::class);
        $notif->method('sendAccountLockout')->willReturn(NotificationResult::success('test-id'));

        $user = new User(['email' => 'test@example.com']);
        $user->failed_login_attempts = 5; // At threshold

        $repo->expects($this->once())
            ->method('incrementFailedLogins')
            ->with($this->isInstanceOf(User::class));

        $repo->expects($this->once())
            ->method('lockAccount')
            ->with($this->isInstanceOf(User::class), 900);

        $service = new AccountLockoutService($repo, $notif);
        $wasLocked = $service->recordFailedAttempt($user, '127.0.0.1');

        $this->assertTrue($wasLocked);
    }
}
