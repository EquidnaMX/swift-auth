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

use PHPUnit\Framework\TestCase;
use Equidna\SwiftAuth\Services\AccountLockoutService;
use Equidna\SwiftAuth\Contracts\UserRepositoryInterface;
use Equidna\SwiftAuth\Models\User;

/**
 * Tests AccountLockoutService behavior for failed login tracking and lockout.
 */
class AccountLockoutServiceTest extends TestCase
{
    /**
     * Returns a test double for NotificationService to avoid mocking final class.
     *
     * @return object  Test double with stubbed notification methods.
     */
    private function createNotificationDouble(): object
    {
        return new class {
            public function sendAccountLockout(string $email, int $duration): ?string
            {
                return 'test-message-id';
            }
        };
    }

    /**
     * Test that recordFailedAttempt increments failed logins.
     */
    public function test_increments_failed_logins(): void
    {
        $repo = $this->createMock(UserRepositoryInterface::class);
        $notif = $this->createNotificationDouble();

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
        $notif = $this->createNotificationDouble();

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
        $notif = $this->createNotificationDouble();

        $user = new User([]);
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
