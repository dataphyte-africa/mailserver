<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrateFoundationAudiencesTest extends TestCase
{
    public function test_preview_and_repeatable_apply_preserve_private_history_and_only_copy_matching_preferences(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation', 'status' => 'active',
            'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $products = [];
        foreach (['activities', 'foundation-newsletters', 'project-update', 'weekly'] as $name) {
            $products[$name] = Product::query()->create([
                'organisation_id' => $organisation->id,
                'name' => ucwords(str_replace('-', ' ', $name)),
                'slug' => 'foundation-newsletters-'.$name,
                'status' => 'active', 'product_type' => 'newsletter',
                'primary_collection_handle' => 'foundation_newsletters',
            ]);
        }
        $legacy = SubscriberGroup::factory()->foundation()->create();
        $weekly = SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $legacy->id, 'name' => 'Weekly Update', 'slug' => 'weekly',
            'purpose' => 'preference',
        ]);
        $activities = SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $legacy->id, 'name' => 'Activities', 'slug' => 'activities',
            'purpose' => 'preference',
        ]);
        $observers = SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $legacy->id, 'name' => 'Observers', 'slug' => 'observers',
            'purpose' => 'preference',
        ]);
        $member = Subscriber::factory()->create(['status' => 'active']);
        $optedOut = Subscriber::factory()->create(['status' => 'unsubscribed']);
        $observer = Subscriber::factory()->create(['status' => 'active']);
        $regularMember = Subscriber::factory()->create(['status' => 'active']);
        $existingWeeklyGroup = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id,
            'product_id' => $products['weekly']->id,
            'name' => 'Weekly Update Subscribers',
            'slug' => 'weekly-update-subscribers',
        ]);
        $regular = SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $existingWeeklyGroup->id, 'name' => 'Regular',
            'slug' => 'regular', 'purpose' => 'preference',
        ]);
        $member->allSubGroups()->attach([$weekly->id, $activities->id], ['subscribed_at' => now()->subMonth()]);
        $optedOut->allSubGroups()->attach($weekly->id, [
            'subscribed_at' => now()->subMonth(), 'unsubscribed_at' => now()->subWeek(),
        ]);
        $observer->allSubGroups()->attach($observers->id, ['subscribed_at' => now()->subMonth()]);
        $regularMember->allSubGroups()->attach($regular->id, ['subscribed_at' => now()->subMonth()]);

        $this->artisan('newsletter:migrate-foundation-audiences')->assertSuccessful();
        $this->assertNull($legacy->fresh()->organisation_id);
        $this->assertSame('Activities', $products['activities']->fresh()->name);

        $this->artisan('newsletter:migrate-foundation-audiences --apply')->assertSuccessful();
        $this->artisan('newsletter:migrate-foundation-audiences --apply')->assertSuccessful();

        $this->assertSame('organisation_private', $legacy->fresh()->audience_scope);
        $this->assertSame($organisation->id, $legacy->fresh()->organisation_id);
        $this->assertTrue($member->subGroups()->whereKey($weekly->id)->exists());
        $this->assertTrue($observer->subGroups()->whereKey($observers->id)->exists());

        foreach (['weekly' => 'Foundation Update', 'activities' => 'Behind the Bytes'] as $key => $name) {
            $product = $products[$key]->fresh();
            $this->assertSame($name, $product->name);
            $this->assertSame('active', $product->status);
            $group = SubscriberGroup::query()->where('product_id', $product->id)->sole();
            $this->assertSame($name.' Subscribers', $group->name);
            $this->assertTrue($group->is_public_preference);
            $this->assertSame(7, $group->subGroups()->where('purpose', 'engagement')->count());
            $preference = $group->subGroups()->where('slug', 'newsletter')->sole();
            $this->assertTrue($member->subGroups()->whereKey($preference->id)->exists());
            $this->assertFalse($observer->allSubGroups()->whereKey($preference->id)->exists());
            $this->assertSame(1, DB::table('subscriber_sub_group')
                ->where('subscriber_id', $member->id)
                ->where('subscriber_sub_group_id', $preference->id)->count());
            if ($key === 'weekly') {
                $this->assertFalse($optedOut->subGroups()->whereKey($preference->id)->exists());
                $this->assertTrue($optedOut->allSubGroups()->whereKey($preference->id)->exists());
                $this->assertTrue($regularMember->subGroups()->whereKey($preference->id)->exists());
                $this->assertFalse($regularMember->subGroups()->whereKey($regular->id)->exists());
                $this->assertNotNull($regular->fresh()->archived_at);
            }
        }

        foreach (['foundation-newsletters', 'project-update'] as $key) {
            $this->assertSame('inactive', $products[$key]->fresh()->status);
        }
    }
}
