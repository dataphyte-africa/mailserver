<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignLinkClick;
use App\Models\CampaignSend;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Tests\TestCase;

class SeedOrganisationDemoDataTest extends TestCase
{
    public function test_fixture_is_scoped_idempotent_and_never_sends_to_pending_or_unsubscribed(): void
    {
        foreach ([
            ['Dataphyte Insight', 'insight-newsletters-data-dive', 'insight_newsletters'],
            ['Policy Point', 'policy-point-newsletters-policy-point', 'policy_point_newsletters'],
            ['Dataphyte Foundation', 'foundation-newsletters-weekly', 'foundation_newsletters'],
        ] as [$name, $slug, $collection]) {
            $organisation = Organisation::query()->create([
                'name' => $name, 'slug' => $slug.'-org', 'status' => 'active',
            ]);
            $product = Product::query()->create([
                'organisation_id' => $organisation->id, 'name' => $name.' Product',
                'slug' => $slug, 'status' => 'active', 'product_type' => 'newsletter',
                'primary_collection_handle' => $collection,
            ]);
            $group = SubscriberGroup::query()->create([
                'organisation_id' => $organisation->id, 'product_id' => $product->id,
                'collection_handle' => $collection, 'name' => $name.' Subscribers',
                'slug' => $slug.'-subscribers',
            ]);
            SubscriberSubGroup::query()->create([
                'subscriber_group_id' => $group->id, 'name' => 'Regular', 'slug' => 'regular',
            ]);
        }

        $this->artisan('newsletter:seed-organisation-demos')->assertSuccessful();
        $this->artisan('newsletter:seed-organisation-demos')->assertSuccessful();

        $campaigns = Campaign::query()->where('name', 'like', 'V2 Demo |%');
        $this->assertSame(6, $campaigns->count());
        $this->assertSame(60, Subscriber::query()->where('metadata->fixture', 'organisation-v2')->count());
        $this->assertSame(108, CampaignSend::query()->whereHas('campaign', fn ($query) => $query->where('name', 'like', 'V2 Demo |%'))->count());
        $this->assertSame(24, CampaignLinkClick::query()->whereHas('campaignSend.campaign', fn ($query) => $query->where('name', 'like', 'V2 Demo |%'))->count());
        $this->assertSame(0, CampaignSend::query()->whereHas('campaign', fn ($query) => $query->where('name', 'like', 'V2 Demo |%'))
            ->whereHas('subscriber', fn ($query) => $query->whereIn('status', ['pending', 'unsubscribed']))->count());
        $this->assertSame(3, $campaigns->distinct()->count('organisation_id'));
    }
}
