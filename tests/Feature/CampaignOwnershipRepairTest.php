<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class CampaignOwnershipRepairTest extends TestCase
{
    public function test_super_admin_can_repair_unowned_legacy_campaign_before_editing(): void
    {
        $this->withoutMiddleware(CountUsers::class);

        $product = $this->createProduct('Foundation Newsletter', 'foundation-newsletter', 'foundation_newsletters');
        $campaign = Campaign::query()->create([
            'name' => 'DF Publication Alert - Mon25May2026',
            'collection' => 'foundation_newsletters',
            'subject' => 'Publication alert',
            'status' => 'draft',
        ]);

        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->assertTrue($user->isSuper());

        $editResponse = $this
            ->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('newsletter.campaigns.edit', $campaign));

        $editResponse
            ->assertOk()
            ->assertSee('Assign Campaign Product')
            ->assertSee('DF Publication Alert - Mon25May2026')
            ->assertSee('Foundation Newsletter');

        $assignResponse = $this
            ->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->post(cp_route('newsletter.campaigns.assign-product.store', $campaign), [
                'product_id' => $product->getKey(),
            ]);

        $assignResponse->assertRedirect(cp_route('newsletter.campaigns.edit', $campaign));

        $campaign->refresh();

        $this->assertSame($product->getKey(), $campaign->product_id);
        $this->assertSame($product->organisation_id, $campaign->organisation_id);
    }

    private function createProduct(string $name, string $slug, string $collectionHandle): Product
    {
        $organisation = Organisation::query()->create([
            'name' => $name.' Organisation',
            'slug' => $slug.'-organisation',
            'status' => 'active',
        ]);

        return Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'product_type' => 'newsletter',
            'primary_collection_handle' => $collectionHandle,
        ]);
    }
}
