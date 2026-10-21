<?php

namespace App\Services\Newsletter;

use App\Models\Organisation;
use App\Models\SubscriberGroup;
use Illuminate\Support\Facades\DB;

class AudienceGrowthService
{
    public function forGroup(SubscriberGroup $group): array
    {
        return $this->summary([$group->getKey()]);
    }

    public function forOrganisation(Organisation $organisation): array
    {
        $groupIds = SubscriberGroup::query()
            ->where('organisation_id', $organisation->getKey())
            ->where(fn ($groups) => $groups
                ->where(fn ($public) => $public->where('audience_scope', 'product_public')
                    ->whereHas('product', fn ($product) => $product
                        ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id')))
                ->orWhere(fn ($private) => $private->where('audience_scope', 'organisation_private')
                    ->whereNull('product_id')))
            ->pluck('id')->all();

        return $this->summary($groupIds);
    }

    private function summary(array $groupIds): array
    {
        $firstJoins = DB::table('subscriber_group_engagements')
            ->whereIn('subscriber_group_id', $groupIds)
            ->selectRaw('subscriber_id, MIN(first_joined_at) AS first_joined_at')
            ->groupBy('subscriber_id');
        $joined = DB::query()->fromSub($firstJoins, 'joined');
        $start = now()->startOfMonth()->subMonths(11);
        $end = now()->startOfMonth()->addMonth();
        $running = (clone $joined)->where('first_joined_at', '<', $start)->count();
        $byMonth = (clone $joined)
            ->where('first_joined_at', '>=', $start)
            ->where('first_joined_at', '<', $end)
            ->selectRaw("DATE_FORMAT(first_joined_at, '%Y-%m') AS month, COUNT(*) AS total")
            ->groupBy('month')->pluck('total', 'month');

        $months = [];
        for ($offset = 0; $offset < 12; $offset++) {
            $month = $start->copy()->addMonths($offset);
            $new = (int) $byMonth->get($month->format('Y-m'), 0);
            $running += $new;
            $months[] = [
                'label' => $month->format('M Y'),
                'new' => $new,
                'total_joined' => $running,
            ];
        }

        $current = DB::table('subscriber_group_engagements as engagement')
            ->join('subscriber_groups as active_group', 'active_group.id', '=', 'engagement.subscriber_group_id')
            ->join('subscribers', 'subscribers.id', '=', 'engagement.subscriber_id')
            ->whereIn('engagement.subscriber_group_id', $groupIds)
            ->whereNull('active_group.archived_at')
            ->where('engagement.is_member', true)
            ->where('subscribers.status', 'active')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('organisation_subscriber_opt_outs as opt_out')
                ->join('subscriber_groups as owned_group', 'owned_group.organisation_id', '=', 'opt_out.organisation_id')
                ->whereColumn('owned_group.id', 'engagement.subscriber_group_id')
                ->whereColumn('opt_out.subscriber_id', 'engagement.subscriber_id'))
            ->distinct()->count('engagement.subscriber_id');

        return [
            'joined' => (clone $joined)->count(),
            'current' => $current,
            'months' => $months,
        ];
    }
}
