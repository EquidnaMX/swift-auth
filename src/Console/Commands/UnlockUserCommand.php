<?php

/**
 * Command to unlock a user account manually.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Console\Commands
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Console\Commands;
use Illuminate\Console\Command;

use Equidna\SwiftAuth\Models\User;

/**
 * Unlocks a user account that has been locked due to failed login attempts.
 */
final class UnlockUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swift-auth:unlock-user {email : The email address of the user to unlock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unlock a user account that has been locked due to failed login attempts';

    /**
     * Executes the console command.
     *
     * @return int  Exit code (0 on success, 1 on failure).
     */
    public function handle(): int
    {
        $rawEmail = $this->argument('email');
        $email = is_string($rawEmail) ? trim($rawEmail) : '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email argument provided.');
            return 1;
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return 1;
        }

        if (!$user->locked_until && $user->failed_login_attempts === 0) {
            $this->info("User '{$email}' is not locked.");
            return 0;
        }

        $user->locked_until = null;
        $user->failed_login_attempts = 0;
        $user->last_failed_login_at = null;
        $user->save();

        logger()->info('swift-auth.unlock-user.manual-unlock', [
            'user_id' => $user->getKey(),
            'email' => $email,
            'admin_action' => true,
        ]);

        $this->info("User '{$email}' has been unlocked successfully.");
        return 0;
    }
}
