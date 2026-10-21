<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Tests\TestCase;

class RepairLegacyInsightDraftAudiencesTest extends TestCase
{
    public function test_only_exact_legacy_drafts_are_repaired_and_preview_is_read_only(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Insight', 'slug' => 'insight', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Marina and Maitama',
            'slug' => 'insight-newsletters-marina-maitama', 'status' => 'active',
            'product_type' => 'newsletter', 'primary_collection_handle' => 'insight_newsletters',
        ]);
        $legacy = SubscriberGroup::factory()->create([
            'organisation_id' => null, 'product_id' => null, 'slug' => 'insight-subscribers',
            'collection_handle' => 'insight_newsletters',
        ]);
        $source = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $legacy->id, 'slug' => 'marina-maitama', 'purpose' => 'preference',
        ]);
        $target = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'collection_handle' => 'insight_newsletters',
        ]);
        $draft = $this->campaign($organisation, $product, 'draft', $source);
        $sent = $this->campaign($organisation, $product, 'sent', $source);

        $this->artisan('newsletter:repair-legacy-insight-drafts')->assertSuccessful();
        $this->assertSame($source->id, (int) $draft->audiences()->first()->targetable_id);

        $this->artisan('newsletter:repair-legacy-insight-drafts --apply')->assertSuccessful();
        $this->artisan('newsletter:repair-legacy-insight-drafts --apply')->assertSuccessful();

        $saved = $draft->audiences()->first();
        $this->assertSame($target->id, (int) $saved->targetable_id);
        $this->assertSame($target->getMorphClass(), $saved->targetable_type);
        $this->assertTrue((bool) $saved->send_to_all);
        $this->assertSame($source->id, (int) $sent->audiences()->first()->targetable_id);
    }

    private function campaign(Organisation $organisation, Product $product, string $status, SubscriberSubGroup $source): Campaign
    {
        $campaign = Campaign::query()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'collection' => 'insight_newsletters', 'name' => $status,
            'subject' => $status, 'status' => $status,
        ]);
        CampaignAudience::query()->create([
            'campaign_id' => $campaign->id, 'targetable_type' => $source->getMorphClass(),
            'targetable_id' => $source->id, 'send_to_all' => false,
        ]);

        return $campaign;
    }
}
