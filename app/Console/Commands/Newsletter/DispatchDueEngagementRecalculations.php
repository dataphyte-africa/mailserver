<?php

namespace App\Console\Commands\Newsletter;

use App\Jobs\Newsletter\RecomputeOrganisationEngagementJob;
use App\Models\Organisation;
use App\Models\OrganisationEngagementSetting;
use Illuminate\Console\Command;

class DispatchDueEngagementRecalculations extends Command
{
    protected $signature = 'newsletter:dispatch-engagement-recalculations';

    protected $description = 'Queue organisation engagement refreshes that have reached their interval.';

    public function handle(): int
    {
        Organisation::query()->where('status', 'active')->chunkById(100, function ($organisations) {
            foreach ($organisations as $organisation) {
                $setting = OrganisationEngagementSetting::query()->firstOrCreate(
                    ['organisation_id' => $organisation->getKey()], ['interval_days' => 30],
                );
                $due = ! $setting->last_recalculated_at
                    || $setting->last_recalculated_at->addDays($setting->interval_days)->isPast();
                $alreadyQueued = in_array($setting->last_status, ['queued', 'running'], true)
                    && $setting->last_requested_at?->greaterThan(now()->subHour());
                if (! $due || $alreadyQueued) {
                    continue;
                }

                $setting->update(['last_requested_at' => now(), 'last_status' => 'queued', 'last_error' => null]);
                RecomputeOrganisationEngagementJob::dispatch((int) $organisation->getKey());
            }
        });

        return self::SUCCESS;
    }
}
