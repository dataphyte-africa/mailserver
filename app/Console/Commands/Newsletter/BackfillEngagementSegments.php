<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Organisation;
use App\Models\OrganisationEngagementSetting;
use App\Models\SubscriberGroup;
use App\Services\Newsletter\GroupEngagementService;
use Illuminate\Console\Command;

class BackfillEngagementSegments extends Command
{
    protected $signature = 'newsletter:backfill-engagement-segments';

    protected $description = 'Populate engagement segments for existing audience members, including legacy unowned groups.';

    public function handle(GroupEngagementService $engagement): int
    {
        Organisation::query()->chunkById(100, function ($organisations) use ($engagement) {
            foreach ($organisations as $organisation) {
                $engagement->recalculateOrganisation((int) $organisation->getKey());
                OrganisationEngagementSetting::query()->updateOrCreate(
                    ['organisation_id' => $organisation->getKey()],
                    ['last_status' => 'complete', 'last_error' => null],
                );
            }
        });

        SubscriberGroup::query()->whereNull('organisation_id')->whereNull('archived_at')
            ->chunkById(100, function ($groups) use ($engagement) {
                foreach ($groups as $group) {
                    $engagement->recalculateGroup($group);
                }
            });

        $this->components->info('Engagement segments backfilled.');

        return self::SUCCESS;
    }
}
