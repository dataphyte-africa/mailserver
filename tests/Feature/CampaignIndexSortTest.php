<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class CampaignIndexSortTest extends TestCase
{
    public function test_campaigns_are_listed_by_sent_date_with_unsent_drafts_last(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $this->actingAs(StatamicUser::findByEmail('admin@mailserver.test'), config('statamic.users.guards.cp', 'web'));

        $organisation = Organisation::query()->create([
            'name' => 'Sort Test', 'slug' => 'sort-test', 'status' => 'active',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id,
            'name' => 'Sort Test Product', 'slug' => 'sort-test-product',
            'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters',
        ]);

        foreach ([
            ['name' => 'Sort Test Draft', 'status' => 'draft', 'created_at' => '2026-10-20 10:00:00', 'sent_at' => null],
            ['name' => 'Sort Test Recent', 'status' => 'sent', 'created_at' => '2026-09-01 10:00:00', 'sent_at' => '2026-10-19 10:00:00'],
            ['name' => 'Sort Test Older', 'status' => 'sent', 'created_at' => '2026-10-18 10:00:00', 'sent_at' => '2026-10-01 10:00:00'],
        ] as $attributes) {
            $campaign = Campaign::query()->create([
                'organisation_id' => $organisation->id,
                'product_id' => $product->id,
                'collection' => 'insight_newsletters',
                'subject' => 'Sort test',
                'name' => $attributes['name'],
                'status' => $attributes['status'],
                'sent_at' => $attributes['sent_at'],
            ]);
            $campaign->timestamps = false;
            $campaign->update(['created_at' => $attributes['created_at']]);
        }

        $this->get(cp_route('newsletter.campaigns.index'))
            ->assertOk()
            ->assertSeeInOrder(['Sort Test Recent', 'Sort Test Older', 'Sort Test Draft']);
    }
}
