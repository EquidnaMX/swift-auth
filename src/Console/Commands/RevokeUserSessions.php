<?php

/**
 * Artisan command to revoke SwiftAuth sessions for a user.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Console\Commands
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Console\Commands;

use Illuminate\Console\Command;
use Equidna\SwiftAuth\Services\SwiftSessionAuth;

/**
 * Enables administrators to revoke sessions from the CLI.
 */
class RevokeUserSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swift-auth:revoke-sessions
        {userId : User ID whose sessions should be revoked}
        {--session=* : Specific session IDs to revoke}
        {--all : Revoke all sessions for the user}
        {--remember : Also clear remember-me tokens for the user}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke one or more SwiftAuth sessions for the given user';

    public function __construct(private SwiftSessionAuth $sessionAuth)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = (int) $this->argument('userId');
        $sessionIds = array_filter((array) $this->option('session'));
        $revokeAll = (bool) $this->option('all');
        $clearRememberTokens = (bool) $this->option('remember');

        if (!$revokeAll && empty($sessionIds)) {
            $this->error('Specify at least one --session or use --all to revoke every session.');

            return Command::INVALID;
        }

        if ($revokeAll) {
            $result = $this->sessionAuth->revokeAllSessionsForUser(
                userId: $userId,
                includeRememberTokens: $clearRememberTokens,
            );

            $this->info("Revoked {$result['deleted_sessions']} session(s) for user {$userId}.");

            if ($result['cleared_remember_tokens'] > 0) {
                $this->info(
                    "Cleared {$result['cleared_remember_tokens']} remember-me token(s) for user {$userId}.",
                );
            }
        } else {
            foreach ($sessionIds as $sessionId) {
                $this->sessionAuth->revokeSession(
                    userId: $userId,
                    sessionId: $sessionId,
                );
            }

            $this->info('Revoked sessions: ' . implode(', ', $sessionIds));

            if ($clearRememberTokens) {
                $cleared = $this->sessionAuth->revokeRememberTokensForUser($userId);

                $this->info("Cleared {$cleared} remember-me token(s) for user {$userId}.");
            }
        }

        return Command::SUCCESS;
    }
}
