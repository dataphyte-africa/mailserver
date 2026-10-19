<?php

namespace Tests\Feature;

use App\Models\Campaign;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class AnalyticsCampaignPaginationTest extends TestCase
{
    public function test_campaign_performance_is_paginated_without_changing_filtered_totals(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->actingAs($user, config('statamic.users.guards.cp', 'web'));

        for ($i = 1; $i <= 12; $i++) {
            Campaign::factory()->sent()->create([
                'name' => sprintf('Insight Campaign %02d', $i),
                'collection' => 'insight_newsletters',
                'sent_at' => now()->subMinutes($i),
            ]);
        }
        Campaign::factory()->sent()->create([
            'name' => 'Foundation Campaign',
            'collection' => 'foundation_newsletters',
        ]);

        $url = cp_route('newsletter.analytics.index', ['collection' => 'insight_newsletters', 'days' => 7]);

        $this->get($url)->assertOk()
            ->assertSee('Insight Campaign 01')
            ->assertSee('Insight Campaign 10')
            ->assertDontSee('Insight Campaign 11')
            ->assertDontSee('Foundation Campaign')
            ->assertSee('Showing 1-10 of 12')
            ->assertSee('12 campaigns')
            ->assertSee('campaign_page=2', false)
            ->assertViewHas('campaigns', fn ($campaigns) => $campaigns->count() === 10
                && str_contains($campaigns->nextPageUrl(), 'collection=insight_newsletters')
                && str_contains($campaigns->nextPageUrl(), 'days=7'))
            ->assertViewHas('totals', fn ($totals) => $totals['campaigns'] === 12);

        $this->get($url.'&campaign_page=2')->assertOk()
            ->assertSee('Insight Campaign 11')
            ->assertSee('Insight Campaign 12')
            ->assertDontSee('Insight Campaign 10')
            ->assertDontSee('Foundation Campaign')
            ->assertSee('Showing 11-12 of 12')
            ->assertViewHas('campaigns', fn ($campaigns) => $campaigns->count() === 2)
            ->assertViewHas('totals', fn ($totals) => $totals['campaigns'] === 12);
    }
}
