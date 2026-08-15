<?php

namespace App\Services\Newsletter;

use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Database\Eloquent\Collection;

class ValidatedCampaignAudience
{
    /**
     * @param  Collection<int, SubscriberSubGroup>  $subGroups
     */
    public function __construct(
        public readonly ?SubscriberGroup $group,
        public readonly Collection $subGroups,
    ) {}

    public function sendsToAll(): bool
    {
        return $this->group !== null;
    }
}
