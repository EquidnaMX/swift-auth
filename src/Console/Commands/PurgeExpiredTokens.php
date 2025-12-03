<?php

/**
 * Artisan command to clean up expired SwiftAuth tokens.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Console\Commands
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 */

namespace Equidna\SwiftAuth\Console\Commands;

use Illuminate\Console\Command;
use Equidna\SwiftAuth\Models\PasswordResetToken;
use Equidna\SwiftAuth\Models\User;

/**
 * Purges expired password reset and email verification tokens.
 */
final class PurgeExpiredTokens extends Command
{
    /**
     * @var string
     */
    protected $signature = 'swift-auth:purge-expired-tokens';

    /**
     * @var string
     */
    protected $description = 'Remove expired SwiftAuth password reset and email verification tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $passwordTtl = (int) config('swift-auth.password_reset_ttl', 900);
        $verificationTtl = (int) config('swift-auth.email_verification.token_ttl', 86400);

        $passwordThreshold = now()->subSeconds($passwordTtl);
        $verificationThreshold = now()->subSeconds($verificationTtl);

        $passwordDeleted = PasswordResetToken::where('created_at', '<', $passwordThreshold)->delete();
        $verificationDeleted = User::whereNotNull('email_verification_token')
            ->whereNotNull('email_verification_sent_at')
            ->where('email_verification_sent_at', '<', $verificationThreshold)
            ->update([
                'email_verification_token' => null,
                'email_verification_sent_at' => null,
            ]);

        $this->info("Deleted {$passwordDeleted} expired password reset tokens.");
        $this->info("Cleared {$verificationDeleted} expired email verification tokens.");

        return self::SUCCESS;
    }
}
