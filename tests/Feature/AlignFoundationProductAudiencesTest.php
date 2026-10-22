<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AlignFoundationProductAudiencesTest extends TestCase
{
    public function test_it_links_only_the_two_restored_audiences_without_moving_memberships(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation', 'status' => 'active',
            'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $products = [];
        foreach (['weekly', 'activities', 'foundation-newsletters', 'project-update'] as $handle) {
            $products[$handle] = Product::query()->create([
                'organisation_id' => $organisation->id,
                'name' => ucwords(str_replace('-', ' ', $handle)),
                'slug' => 'foundation-newsletters-'.$handle,
                'status' => 'active', 'product_type' => 'newsletter',
                'primary_collection_handle' => 'foundation_newsletters',
                'blueprint_handle' => str_replace('-', '_', $handle),
            ]);
        }
        $groups = [];
        foreach (['weekly' => 'Weekly Update', 'activities' => 'Activities', 'osun' => 'Osun Election Observers 2026'] as $handle => $name) {
            $groups[$handle] = SubscriberGroup::query()->create([
                'organisation_id' => $organisation->id, 'product_id' => null,
                'audience_scope' => 'organisation_private',
                'name' => $name, 'slug' => 'foundation-private-'.$handle,
                'collection_handle' => 'foundation_newsletters', 'is_public_preference' => false,
            ]);
            SubscriberSubGroup::query()->create([
                'subscriber_group_id' => $groups[$handle]->id,
                'name' => 'Members', 'slug' => 'members', 'purpose' => 'preference',
            ]);
        }
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $weeklyMembers = $groups['weekly']->subGroups()->where('slug', 'members')->sole();
        $subscriber->allSubGroups()->attach($weeklyMembers->id, ['subscribed_at' => now()->subMonth()]);
        $membershipId = DB::table('subscriber_sub_group')
            ->where('subscriber_sub_group_id', $weeklyMembers->id)->value('id');

        $this->artisan('newsletter:align-foundation-product-audiences')->assertSuccessful();
        $this->assertNull($groups['weekly']->fresh()->product_id);
        $this->artisan('newsletter:align-foundation-product-audiences --apply')->assertSuccessful();
        $this->artisan('newsletter:align-foundation-product-audiences --apply')->assertSuccessful();

        foreach (['weekly' => 'Foundation Update', 'activities' => 'Behind the Bytes'] as $handle => $name) {
            $product = $products[$handle]->fresh();
            $group = $groups[$handle]->fresh();
            $this->assertSame($name, $product->name);
            $this->assertSame('active', $product->status);
            $this->assertSame($product->id, $group->product_id);
            $this->assertSame('product_public', $group->audience_scope);
            $this->assertTrue($group->is_public_preference);
            $this->assertSame($name.' Subscribers', $group->name);
            $this->assertSame('newsletter', $group->subGroups()->where('purpose', 'preference')->sole()->slug);
        }
        $this->assertSame($membershipId, DB::table('subscriber_sub_group')
            ->where('subscriber_sub_group_id', $weeklyMembers->id)->value('id'));
        $this->assertSame('organisation_private', $groups['osun']->fresh()->audience_scope);
        $this->assertSame('inactive', $products['foundation-newsletters']->fresh()->status);
        $this->assertSame('inactive', $products['project-update']->fresh()->status);
        $this->assertSame(3, SubscriberGroup::query()->count());
    }
}
