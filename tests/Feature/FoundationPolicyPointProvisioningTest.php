<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Services\Forms\ProductFormSubscriptionService;
use App\Services\Newsletter\GroupEngagementService;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection as StatamicCollection;
use Tests\TestCase;

class FoundationPolicyPointProvisioningTest extends TestCase
{
    public function test_it_provisions_an_independent_foundation_product_and_public_audience(): void
    {
        StatamicCollection::make('foundation_newsletters')->title('Foundation Newsletters')->save();
        $foundation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $policyOrganisation = Organisation::query()->create([
            'name' => 'Policy Point', 'slug' => 'policy-point-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'policy_point_newsletters',
        ]);
        $existingProduct = Product::query()->create([
            'organisation_id' => $policyOrganisation->id, 'name' => 'Policy Point',
            'slug' => 'policy-point-newsletters-policy-point', 'status' => 'active',
            'product_type' => 'newsletter', 'primary_collection_handle' => 'policy_point_newsletters',
            'blueprint_handle' => 'policy_point',
        ]);

        $this->artisan('newsletter:provision-foundation-policy-point')->assertSuccessful();
        $this->assertDatabaseMissing('products', ['slug' => 'foundation-newsletters-policy-point']);

        $this->artisan('newsletter:provision-foundation-policy-point --apply')->assertSuccessful();
        $this->artisan('newsletter:provision-foundation-policy-point --apply')->assertSuccessful();

        $product = Product::query()->where('slug', 'foundation-newsletters-policy-point')->sole();
        $group = SubscriberGroup::query()->where('slug', 'foundation-policy-point-subscribers')->sole();
        $this->assertSame($foundation->id, $product->organisation_id);
        $this->assertSame('foundation_newsletters', $product->primary_collection_handle);
        $this->assertSame('policy_point', $product->blueprint_handle);
        $this->assertNotNull(Blueprint::find('collections.foundation_newsletters.policy_point'));
        $this->assertTrue(collect(StatamicCollection::findByHandle('foundation_newsletters')->entryBlueprints())
            ->contains(fn ($blueprint) => $blueprint->handle() === 'policy_point'));
        $this->assertSame($product->id, $group->product_id);
        $this->assertSame('product_public', $group->audience_scope);
        $this->assertTrue($group->is_public_preference);
        $this->assertSame('newsletter', $group->subGroups()->where('purpose', 'preference')->sole()->slug);
        $this->assertSame(count(GroupEngagementService::SEGMENTS), $group->subGroups()->where('purpose', 'engagement')->count());
        $this->assertSame([$group->id], app(ProductFormSubscriptionService::class)
            ->availablePreferencesForOrganisations([$foundation->id])->modelKeys());
        $this->assertSame([], app(ProductFormSubscriptionService::class)
            ->availablePreferencesForOrganisations([$policyOrganisation->id])->modelKeys());
        $this->assertSame($policyOrganisation->id, $existingProduct->fresh()->organisation_id);
        $this->assertSame(2, Product::query()->count());
    }

    public function test_it_refuses_a_product_slug_owned_by_another_organisation(): void
    {
        StatamicCollection::make('foundation_newsletters')->title('Foundation Newsletters')->save();
        Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $policyOrganisation = Organisation::query()->create([
            'name' => 'Policy Point', 'slug' => 'policy-point-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'policy_point_newsletters',
        ]);
        Product::query()->create([
            'organisation_id' => $policyOrganisation->id, 'name' => 'Policy Point',
            'slug' => 'foundation-newsletters-policy-point', 'status' => 'active',
            'product_type' => 'newsletter', 'primary_collection_handle' => 'policy_point_newsletters',
            'blueprint_handle' => 'policy_point',
        ]);

        $this->artisan('newsletter:provision-foundation-policy-point --apply')->assertFailed();
        $this->assertDatabaseCount('subscriber_groups', 0);
    }
}
