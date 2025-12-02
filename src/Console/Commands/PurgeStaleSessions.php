<?php

/**
 * Artisan command to purge stale session records.
 *
 * PHP 8.2+
 *
 * @package   Equidna\SwiftAuth\Console\Commands
 * @author    Gabriel Ruelas <gruelas@gruelas.com>
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/EquidnaMX/swift_auth
 */

namespace Equidna\SwiftAuth\Console\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

use Equidna\SwiftAuth\Models\UserSession;

/**
 * Removes session rows that have exceeded idle or absolute lifetimes.
 */
class PurgeStaleSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swift-auth:purge-stale-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired SwiftAuth session records based on configured lifetimes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $idleTimeout = (int) config('swift-auth.session_lifetimes.idle_timeout_seconds', 0);
        $absoluteTimeout = (int) config('swift-auth.session_lifetimes.absolute_timeout_seconds', 0);
        $graceSeconds = (int) config('swift-auth.session_cleanup.grace_seconds', 0);

        $now = Carbon::now();

        $idleCutoff = $idleTimeout > 0 ? $now->copy()->subSeconds($idleTimeout + $graceSeconds) : null;
        $absoluteCutoff = $absoluteTimeout > 0 ? $now->copy()->subSeconds($absoluteTimeout + $graceSeconds) : null;

        if ($idleCutoff === null && $absoluteCutoff === null) {
            $this->info('No session lifetimes configured; skipping purge.');

            return Command::SUCCESS;
        }

        $deleted = UserSession::query()
            ->when(
                $idleCutoff !== null,
                fn ($query) => $query->where('last_activity', '<', $idleCutoff),
            )
            ->when(
                $absoluteCutoff !== null,
                function ($query) use ($idleCutoff, $absoluteCutoff) {
                    return $idleCutoff !== null
                        ? $query->orWhere('created_at', '<', $absoluteCutoff)
                        : $query->where('created_at', '<', $absoluteCutoff);
                },
            )
            ->delete();

        $this->info("Deleted {$deleted} stale session(s).");

        return Command::SUCCESS;
    }
}
