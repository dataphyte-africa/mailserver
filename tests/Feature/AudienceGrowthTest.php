<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\AudienceGrowthService;
use App\Services\Newsletter\GroupEngagementService;
use Tests\TestCase;

class AudienceGrowthTest extends TestCase
{
    public function test_organisation_counts_unique_joins_and_current_members_after_unsubscribe(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Growth Test', 'slug' => 'growth-test', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $groups = collect([1, 2])->map(fn ($number) => SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id,
            'product_id' => null,
            'audience_scope' => 'organisation_private',
            'name' => 'Private '.$number,
        ]));
        $first = Subscriber::factory()->create(['status' => 'active']);
        $second = Subscriber::factory()->create(['status' => 'active']);
        $preferences = $groups->map(fn ($group) => SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $group->id,
            'purpose' => 'preference',
        ]));
        $first->allSubGroups()->attach($preferences->pluck('id')->all(), ['subscribed_at' => now()->subMonth()]);
        $second->allSubGroups()->attach($preferences->first()->id, ['subscribed_at' => now()]);
        $ratings = app(GroupEngagementService::class);
        $ratings->syncForSubscriber($first);
        $ratings->syncForSubscriber($second);

        $growth = app(AudienceGrowthService::class)->forOrganisation($organisation);
        $this->assertSame(2, $growth['joined']);
        $this->assertSame(2, $growth['current']);
        $this->assertSame(1, $growth['months'][11]['new']);

        $second->allSubGroups()->updateExistingPivot($preferences->first()->id, ['unsubscribed_at' => now()]);
        $ratings->sync($second, $groups->first());
        $growth = app(AudienceGrowthService::class)->forOrganisation($organisation);
        $this->assertSame(2, $growth['joined']);
        $this->assertSame(1, $growth['current']);
        $this->assertSame(1, app(AudienceGrowthService::class)->forGroup($groups->first())['current']);
    }
}
