<?php

namespace App\Console\Commands\Newsletter;

use App\Services\Newsletter\CampaignStatsSyncService;
use Illuminate\Console\Command;

/**
 * Fallback stats reconciliation command.
 *
 * Designed to run as two scheduled jobs:
 *
 *  JOB 1 — Recent (hourly, low cost):
 *    Checks sends from the last SYNC_RECENT_HOURS hours.
 *    Catches deliveries, opens, and clicks shortly after sending.
 *    Limit: SYNC_RECENT_LIMIT sends per run.
 *
 *  JOB 2 — Deep scan (daily at 2 AM, off-peak):
 *    Checks all unresolved sends from the last SYNC_DEEP_DAYS days.
 *    Catches late opens/clicks from subscribers who engage days later.
 *    Limit: SYNC_DEEP_LIMIT sends per run.
 *
 * Usage:
 *   php artisan campaigns:sync-stats                        ← all unresolved, no limit
 *   php artisan campaigns:sync-stats --hours=8 --limit=500  ← recent job
 *   php artisan campaigns:sync-stats --days=30 --limit=2000 ← deep scan job
 *   php artisan campaigns:sync-stats --campaign=123 --dry-run
 */
class SyncCampaignStats extends Command
{
    protected $signature = 'campaigns:sync-stats
                              {--campaign= : Sync a specific campaign ID only}
                              {--hours=    : Look back N hours (overrides --days)}
                              {--days=     : Look back N days (default: 30)}
                              {--limit=    : Max sends to process per run (0 = unlimited)}
                              {--dry-run   : Report what would be synced without writing}';

    protected $description = 'Reconcile campaign delivery stats from Elastic Email API (fallback for missed webhooks)';

    /* ------------------------------------------------------------------ */

    public function handle(): void
    {
        $hours      = $this->option('hours') ? (int) $this->option('hours') : null;
        $days       = $this->option('days')  ? (int) $this->option('days')  : 30;
        $limit      = $this->option('limit') !== null ? (int) $this->option('limit') : 0;
        $campaignId = $this->option('campaign') ? (int) $this->option('campaign') : null;
        $dryRun     = (bool) $this->option('dry-run');

        $window = $hours ? "{$hours}h" : "{$days}d";
        $cap    = $limit > 0 ? " (cap: {$limit})" : '';
        $this->info("Syncing campaign stats (window: {$window}{$cap})" . ($dryRun ? ' [dry-run]' : '') . '...');

        $result = app(CampaignStatsSyncService::class)->sync(
            campaignId: $campaignId,
            hours: $hours,
            days: $days,
            limit: $limit,
            dryRun: $dryRun,
            applyWindow: true,
        );

        if (! ($result['ok'] ?? false)) {
            $this->error((string) ($result['error'] ?? 'Unable to sync campaign stats.'));
            return;
        }

        $total = (int) ($result['total'] ?? 0);
        $synced = (int) ($result['synced'] ?? 0);

        if ($total === 0) {
            $this->line('No unresolved sends found.');
            return;
        }

        $this->info("Found {$total} sends to check.");

        foreach ($result['errors'] ?? [] as $error) {
            $this->warn("  {$error}");
        }

        $this->info("Queued {$synced} / {$total} sync webhook jobs.");
    }
}
