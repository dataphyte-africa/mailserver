<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MigrateLegacyInsightPreferencesTest extends TestCase
{
    public function test_preview_is_read_only_and_apply_preserves_opt_outs_and_unmapped_preferences(): void
    {
        Mail::fake();
        $organisation = Organisation::query()->create([
            'name' => 'Insight', 'slug' => 'insight', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $legacy = SubscriberGroup::factory()->create([
            'organisation_id' => null, 'product_id' => null,
            'name' => 'Insight Subscribers', 'slug' => 'insight-subscribers',
            'collection_handle' => 'insight_newsletters',
        ]);
        $sources = [];
        foreach (['data-dive', 'marina-maitama', 'pocket-science', 'senorrita'] as $slug) {
            Product::query()->create([
                'organisation_id' => $organisation->id, 'name' => ucwords(str_replace('-', ' ', $slug)),
                'slug' => 'insight-newsletters-'.$slug, 'status' => 'active',
                'product_type' => 'newsletter', 'primary_collection_handle' => 'insight_newsletters',
            ]);
            $sources[$slug] = SubscriberSubGroup::factory()->create([
                'subscriber_group_id' => $legacy->id, 'slug' => $slug, 'name' => $slug,
            ]);
        }
        $webinars = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $legacy->id, 'slug' => 'webinars', 'name' => 'Webinars',
        ]);
        $active = Subscriber::factory()->create(['status' => 'active']);
        $optedOut = Subscriber::factory()->create(['status' => 'unsubscribed']);
        $active->allSubGroups()->attach($sources['data-dive']->id, ['subscribed_at' => now()->subMonth()]);
        $active->allSubGroups()->attach($webinars->id, ['subscribed_at' => now()->subMonth()]);
        $optedOut->allSubGroups()->attach($sources['data-dive']->id, [
            'subscribed_at' => now()->subMonth(), 'unsubscribed_at' => now()->subWeek(),
        ]);

        $this->artisan('newsletter:migrate-legacy-insight-preferences')->assertSuccessful();
        $this->assertSame(1, SubscriberGroup::query()->count());

        $this->artisan('newsletter:migrate-legacy-insight-preferences --apply')->assertSuccessful();
        $this->artisan('newsletter:migrate-legacy-insight-preferences --apply')->assertSuccessful();

        $group = SubscriberGroup::query()->where('product_id', Product::query()
            ->where('slug', 'insight-newsletters-data-dive')->value('id'))->firstOrFail();
        $this->assertTrue($group->is_public_preference);
        $target = $group->subGroups()->where('slug', 'newsletter')->firstOrFail();
        $this->assertTrue($active->subGroups()->whereKey($target->id)->exists());
        $this->assertFalse($active->subGroups()->whereKey($sources['data-dive']->id)->exists());
        $this->assertTrue($active->subGroups()->whereKey($webinars->id)->exists());
        $this->assertFalse($optedOut->subGroups()->whereKey($target->id)->exists());
        $this->assertSame(1, $optedOut->allSubGroups()->whereKey($target->id)->count());
        $this->assertSame(4, SubscriberGroup::query()->where('is_public_preference', true)->count());
        Mail::assertNothingSent();
    }
}
