<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\CampaignSend;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberGroupEngagement;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\GroupEngagementService;
use App\Services\Newsletter\SubscriberEngagementService;
use App\Services\Newsletter\CampaignAudienceOwnershipService;
use App\Exceptions\Newsletter\CampaignAudienceOwnershipException;
use Tests\TestCase;

class GroupEngagementServiceTest extends TestCase
{
    public function test_backfill_includes_legacy_groups_without_changing_ownership(): void
    {
        $group = SubscriberGroup::factory()->create(['organisation_id' => null, 'product_id' => null]);
        $preference = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->id]);
        $subscriber = Subscriber::factory()->create();
        $subscriber->subGroups()->attach($preference->id, ['subscribed_at' => now()->subMonth()]);

        $this->artisan('newsletter:backfill-engagement-segments')->assertSuccessful();

        $this->assertNull($group->fresh()->organisation_id);
        $this->assertSame('new', SubscriberGroupEngagement::query()
            ->where('subscriber_id', $subscriber->id)
            ->where('subscriber_group_id', $group->id)
            ->firstOrFail()->rating);
        $this->assertTrue($subscriber->subGroups()->where('slug', 'rating-new')->exists());
        $this->assertTrue($subscriber->subGroups()->whereKey($preference->id)->exists());
    }

    public function test_score_uses_ten_most_recent_sends_not_ten_most_recent_interactions(): void
    {
        $subscriber = Subscriber::factory()->create();
        CampaignSend::factory()->create([
            'campaign_id' => Campaign::factory()->create()->id,
            'subscriber_id' => $subscriber->id,
            'status' => 'clicked',
            'sent_at' => now()->subDays(20),
            'clicked_at' => now(),
        ]);

        foreach (range(1, 10) as $daysAgo) {
            CampaignSend::factory()->create([
                'campaign_id' => Campaign::factory()->create()->id,
                'subscriber_id' => $subscriber->id,
                'status' => 'sent',
                'sent_at' => now()->subDays($daysAgo),
            ]);
        }

        $rating = app(SubscriberEngagementService::class)->compute($subscriber);

        $this->assertSame(0, $rating['engagement_score']);
        $this->assertSame('inactive', $rating['engagement_rating']);
    }

    public function test_ratings_are_per_group_and_first_join_date_survives_recalculation(): void
    {
        $subscriber = Subscriber::factory()->create();
        $first = SubscriberGroup::factory()->create();
        $second = SubscriberGroup::factory()->create();
        foreach ([$first, $second] as $group) {
            $preference = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->id]);
            $subscriber->subGroups()->attach($preference->id, ['subscribed_at' => now()->subMonths(2)]);
        }

        $ratings = app(GroupEngagementService::class);
        $ratings->syncForSubscriber($subscriber);
        $joinedAt = SubscriberGroupEngagement::query()->where('subscriber_id', $subscriber->id)
            ->where('subscriber_group_id', $first->id)->firstOrFail()->first_joined_at;
        $this->assertSame('new', SubscriberGroupEngagement::query()->where('subscriber_group_id', $second->id)->firstOrFail()->rating);

        foreach (range(1, 2) as $issue) {
            $campaign = Campaign::factory()->create();
            CampaignAudience::query()->create([
                'campaign_id' => $campaign->id, 'targetable_type' => 'subscriber_group',
                'targetable_id' => $first->id, 'send_to_all' => true,
            ]);
            CampaignSend::factory()->create([
                'campaign_id' => $campaign->id, 'subscriber_id' => $subscriber->id,
                'status' => 'clicked', 'clicked_at' => now(),
            ]);
        }

        $ratings->syncForSubscriber($subscriber);
        $firstRating = SubscriberGroupEngagement::query()->where('subscriber_id', $subscriber->id)
            ->where('subscriber_group_id', $first->id)->firstOrFail();
        $this->assertSame('high', $firstRating->rating);
        $this->assertTrue($firstRating->first_joined_at->equalTo($joinedAt));
        $this->assertSame('new', SubscriberGroupEngagement::query()->where('subscriber_group_id', $second->id)->firstOrFail()->rating);
        $this->assertTrue($subscriber->subGroups()->where('slug', 'rating-high')->exists());
        $this->assertFalse($subscriber->subGroups()->where('slug', 'rating-suppressed')->exists());
    }

    public function test_suppressed_segment_cannot_be_selected_as_campaign_audience(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Engagement Test', 'slug' => 'engagement-test', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Test Product',
            'slug' => 'engagement-test-product', 'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'collection_handle' => 'insight_newsletters',
        ]);
        app(GroupEngagementService::class)->ensureSegments($group);
        $suppressed = $group->subGroups()->where('slug', 'rating-suppressed')->firstOrFail();

        $this->expectException(CampaignAudienceOwnershipException::class);
        app(CampaignAudienceOwnershipService::class)->validateForProduct($product, false, [$suppressed->id]);
    }
}
