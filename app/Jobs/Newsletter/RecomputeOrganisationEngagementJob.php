<?php

namespace App\Jobs\Newsletter;

use App\Models\OrganisationEngagementSetting;
use App\Services\Newsletter\GroupEngagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RecomputeOrganisationEngagementJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $organisationId) {}

    public function uniqueId(): string
    {
        return (string) $this->organisationId;
    }

    public function handle(GroupEngagementService $engagement): void
    {
        $setting = OrganisationEngagementSetting::query()->firstOrCreate(
            ['organisation_id' => $this->organisationId], ['interval_days' => 30],
        );
        $setting->update(['last_status' => 'running', 'last_error' => null]);
        $engagement->recalculateOrganisation($this->organisationId);
        $setting->refresh()->update(['last_status' => 'complete']);
    }

    public function failed(Throwable $exception): void
    {
        OrganisationEngagementSetting::query()->where('organisation_id', $this->organisationId)
            ->update(['last_status' => 'failed', 'last_error' => mb_substr($exception->getMessage(), 0, 2000)]);
    }
}
