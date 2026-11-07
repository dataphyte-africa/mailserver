<?php

namespace Tests\Feature;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Organisation;
use App\Models\OrganisationUserScope;
use App\Models\Product;
use App\Models\ProductUserScope;
use App\Models\SubscriberGroup;
use Illuminate\Support\Str;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\Authorize;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class ActiveOrganisationContextTest extends TestCase
{
    public function test_delegated_organisations_are_separated_by_active_context(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $this->withoutMiddleware(Authorize::class);

        [$insight, $insightProduct] = $this->organisationWithProduct('Insight');
        [$foundation, $foundationProduct] = $this->organisationWithProduct('Foundation');
        [$academy, $academyProduct] = $this->organisationWithProduct('Academy');
        [$foreign] = $this->organisationWithProduct('Policy Point');

        foreach ([$insightProduct, $foundationProduct, $academyProduct] as $product) {
            SubscriberGroup::factory()->create([
                'organisation_id' => $product->organisation_id,
                'product_id' => $product->id,
                'collection_handle' => $product->primary_collection_handle,
                'audience_scope' => 'product_public',
                'name' => $product->name.' Audience',
            ]);
        }

        $operator = StatamicUser::make()->id((string) Str::uuid())->email('context-'.Str::random(8).'@example.test');
        $operator->assignRole('scoped_operator');
        $operator->save();

        try {
            $identity = app(StatamicUserIdentityBridgeInterface::class)->provision($operator);
            foreach ([$insight, $academy] as $organisation) {
                OrganisationUserScope::query()->create([
                    'user_id' => $identity->id,
                    'organisation_id' => $organisation->id,
                    'scope_role' => 'organisation_operator',
                    'status' => 'active',
                ]);
            }
            ProductUserScope::query()->create([
                'user_id' => $identity->id,
                'product_id' => $foundationProduct->id,
                'scope_role' => 'product_operator',
                'status' => 'active',
            ]);

            $this->actingAs($operator, config('statamic.users.guards.cp', 'web'));

            $this->get(cp_route('product-banners.index'))->assertOk()
                ->assertSee($insightProduct->name)->assertDontSee($foundationProduct->name)
                ->assertDontSee($academyProduct->name);
            $this->get(cp_route('newsletter.groups.index'))->assertOk()
                ->assertSee($insightProduct->name.' Audience')
                ->assertDontSee($foundationProduct->name.' Audience');
            $this->get(cp_route('organisation-profiles.index'))->assertOk()
                ->assertSee('name="organisation-profile"', false)
                ->assertSee($insight->name)->assertSee($academy->name)
                ->assertDontSee($foundation->name);
            $this->get(cp_route('newsletter.organisation-context.index'))->assertOk()
                ->assertSee($insight->name)->assertSee($academy->name)
                ->assertDontSee($foundation->name);
            $this->put(cp_route('organisation-profiles.update', $academy), [
                'name' => 'Academy Profile Updated',
            ])->assertRedirect();
            $this->assertSame('Academy Profile Updated', $academy->fresh()->name);
            $this->put(cp_route('organisation-profiles.update', $foundation), [
                'name' => 'Not Delegated',
            ])->assertForbidden();

            $this->post(cp_route('newsletter.organisation-context.update'), [
                'organisation_id' => $foundation->id,
            ])->assertForbidden();
            $this->get(cp_route('product-banners.index'))->assertOk()
                ->assertSee($insightProduct->name)->assertDontSee($foundationProduct->name);
            $this->get(cp_route('newsletter.engagement.index'))->assertOk()
                ->assertSee($insight->name)->assertDontSee($foundation->name);
            $this->put(cp_route('product-banners.update', $insightProduct), [
                'remove_banner' => true,
            ])->assertRedirect();
            $this->put(cp_route('product-banners.update', $foundationProduct), [
                'remove_banner' => true,
            ])->assertForbidden();
            $this->post(cp_route('newsletter.organisation-context.update'), [
                'organisation_id' => $academy->id,
            ])->assertRedirect(cp_route('dashboard'));
            $this->get(cp_route('product-banners.index'))->assertOk()
                ->assertSee('<h2>'.$academyProduct->name.'</h2>', false)
                ->assertDontSee('<h2>'.$insightProduct->name.'</h2>', false);
            $this->assertSame(1, ProductUserScope::query()->where('user_id', $identity->id)
                ->where('product_id', $foundationProduct->id)->where('status', 'active')->count());
            $this->post(cp_route('newsletter.organisation-context.update'), [
                'organisation_id' => $foreign->id,
            ])->assertForbidden();
        } finally {
            $operator->delete();
        }
    }

    private function organisationWithProduct(string $name): array
    {
        $slug = Str::slug($name);
        $organisation = Organisation::query()->create([
            'name' => $name, 'slug' => $slug, 'status' => 'active',
            'primary_collection_handle' => $slug.'_newsletters',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id,
            'name' => $name.' Product', 'slug' => $slug.'-product',
            'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => $slug.'_newsletters',
        ]);

        return [$organisation, $product];
    }
}
