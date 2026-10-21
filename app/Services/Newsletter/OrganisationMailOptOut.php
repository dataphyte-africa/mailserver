<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Facades\DB;

class OrganisationMailOptOut
{
    public function has(int $organisationId, int $subscriberId): bool
    {
        return DB::table('organisation_subscriber_opt_outs')
            ->where('organisation_id', $organisationId)
            ->where('subscriber_id', $subscriberId)->exists();
    }

    public function set(int $organisationId, int $subscriberId, bool $optedOut): void
    {
        $query = DB::table('organisation_subscriber_opt_outs')
            ->where('organisation_id', $organisationId)->where('subscriber_id', $subscriberId);

        if ($optedOut) {
            DB::table('organisation_subscriber_opt_outs')->insertOrIgnore([
                'organisation_id' => $organisationId,
                'subscriber_id' => $subscriberId,
                'created_at' => now(),
            ]);
        } else {
            $query->delete();
        }
    }
}
