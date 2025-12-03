<?php

/**
 * Artisan command to list active SwiftAuth sessions.
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
use Illuminate\Support\Collection;
use Equidna\SwiftAuth\Models\UserSession;
use Equidna\SwiftAuth\Services\SwiftSessionAuth;

/**
 * Displays active sessions for governance and auditing.
 */
class ListSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swift-auth:sessions {userId? : Filter by user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List SwiftAuth sessions, optionally filtering by user ID';

    public function __construct(private SwiftSessionAuth $sessionAuth)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = $this->argument('userId');

        $sessions = $this->sessionAuth->allSessions();

        if ($userId !== null) {
            $sessions = $sessions->where('id_user', (int) $userId);
        }

        if ($sessions->isEmpty()) {
            $this->info('No sessions found.');

            return Command::SUCCESS;
        }

        $this->table(
            headers: [
                'Session ID',
                'User ID',
                'Device',
                'IP Address',
                'Platform',
                'Browser',
                'Last Activity',
            ],
            rows: $this->mapSessions($sessions),
        );

        return Command::SUCCESS;
    }

    /**
     * Maps session models into tabular rows.
     *
     * @param  Collection<int, \Equidna\SwiftAuth\Models\UserSession> $sessions  Session collection.
     * @return array<int, array<int, string|null>>
     */
    private function mapSessions(Collection $sessions): array
    {
        return $sessions
            ->map(
                /** @param UserSession $session */
                fn($session) => [
                    $session->session_id,
                    (string) $session->id_user,
                    $session->device_name,
                    $session->ip_address,
                    $session->platform,
                    $session->browser,
                    $session->last_activity instanceof \DateTimeInterface
                        ? $session->last_activity->format('Y-m-d H:i:s')
                        : null,
                ]
            )
            ->all();
    }
}
