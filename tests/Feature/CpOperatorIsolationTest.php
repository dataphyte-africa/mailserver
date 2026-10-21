<?php

namespace Tests\Feature;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Campaign;
use App\Models\EntryModel;
use App\Models\Organisation;
use App\Models\OrganisationUserScope;
use App\Models\Product;
use App\Models\ProductUserScope;
use App\Models\ProductForm;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Policies\ScopedEntryPolicy;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Statamic\Facades\User as StatamicUser;
use Statamic\Facades\Role as StatamicRole;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Fields\Blueprint;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class CpOperatorIsolationTest extends TestCase
{
    public function test_legacy_collection_records_require_access_to_every_product_in_one_organisation(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\Authorize::class);
        $first = $this->product('Legacy First', 'insight_newsletters');
        $second = Product::query()->create([
            'organisation_id' => $first->organisation_id, 'name' => 'Legacy Second', 'slug' => 'legacy-second',
            'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters', 'blueprint_handle' => 'legacy-second',
        ]);
        $foreign = $this->product('Legacy Foreign', 'foundation_newsletters');
        $legacyGroup = SubscriberGroup::query()->create([
            'name' => 'Historical Insight Audience', 'slug' => 'historical-insight-audience',
            'collection_handle' => 'insight_newsletters',
        ]);
        $foreignGroup = SubscriberGroup::query()->create([
            'name' => 'Historical Foundation Audience', 'slug' => 'historical-foundation-audience',
            'collection_handle' => 'foundation_newsletters',
        ]);
        $legacySubscriber = $this->subscriber($legacyGroup);
        $foreignSubscriber = $this->subscriber($foreignGroup);
        $legacyCampaign = Campaign::query()->create([
            'name' => 'Historical Insight Campaign', 'collection' => 'insight_newsletters',
            'subject' => 'Legacy', 'status' => 'draft',
        ]);
        $foreignCampaign = Campaign::query()->create([
            'name' => 'Historical Foundation Campaign', 'collection' => 'foundation_newsletters',
            'subject' => 'Legacy', 'status' => 'draft',
        ]);

        $user = StatamicUser::make()->id((string) Str::uuid())->email('legacy-scope-'.Str::random(8).'@example.test');
        $user->assignRole('scoped_operator');
        $user->save();

        try {
            $identity = app(StatamicUserIdentityBridgeInterface::class)->provision($user);
            ProductUserScope::query()->create([
                'user_id' => $identity->id, 'product_id' => $first->id,
                'scope_role' => 'product_operator', 'status' => 'active',
            ]);
            $scope = app(CpOperatorScope::class);
            $this->assertSame([], $scope->legacyCollectionHandles($user));
            $this->assertFalse($scope->canSubscriber($user, $legacySubscriber));
            $this->assertFalse($scope->canCampaign($user, $legacyCampaign));

            OrganisationUserScope::query()->create([
                'user_id' => $identity->id, 'organisation_id' => $first->organisation_id,
                'scope_role' => 'organisation_operator', 'status' => 'active',
            ]);
            $this->assertContains('insight_newsletters', $scope->legacyCollectionHandles($user));
            $this->assertTrue($scope->canSubscriber($user, $legacySubscriber));
            $this->assertTrue($scope->canCampaign($user, $legacyCampaign));
            $this->assertFalse($scope->canSubscriber($user, $foreignSubscriber));
            $this->assertFalse($scope->canCampaign($user, $foreignCampaign));
            $this->assertFalse($scope->canGroup($user, $legacyGroup));

            $this->actingAs($user, config('statamic.users.guards.cp', 'web'));
            $this->get(cp_route('newsletter.subscribers.index'))->assertOk()
                ->assertSee($legacySubscriber->email)->assertDontSee($foreignSubscriber->email)
                ->assertDontSee(cp_route('newsletter.subscribers.edit', $legacySubscriber));
            $this->get(cp_route('newsletter.campaigns.index'))->assertOk()
                ->assertSee($legacyCampaign->name)->assertDontSee($foreignCampaign->name);
            $this->get(cp_route('newsletter.groups.index'))->assertOk()
                ->assertSee($legacyGroup->name)->assertDontSee($foreignGroup->name);
            $this->get(cp_route('newsletter.subscribers.show', $legacySubscriber))->assertOk();
            $this->get(cp_route('newsletter.subscribers.edit', $legacySubscriber))->assertForbidden();
            $this->get(cp_route('newsletter.campaigns.show', $legacyCampaign))->assertOk();
            $legacyCampaign->update(['status' => 'sent', 'sent_at' => now()]);
            $this->get(cp_route('newsletter.analytics.index'))->assertOk()
                ->assertSee($legacyCampaign->name)->assertDontSee($foreignCampaign->name)
                ->assertSee('last 90 days');
            $this->post(cp_route('newsletter.campaigns.send', $legacyCampaign))->assertForbidden();
            $this->get(cp_route('newsletter.campaigns.show', $foreignCampaign))->assertForbidden();

            $foreign->update(['primary_collection_handle' => 'insight_newsletters']);
            $this->assertNotContains('insight_newsletters', $scope->legacyCollectionHandles($user));
            $this->assertFalse($scope->canSubscriber($user, $legacySubscriber));
            $this->assertFalse($scope->canCampaign($user, $legacyCampaign));
        } finally {
            $user->delete();
        }
    }

    public function test_product_blueprint_create_and_store_require_a_scoped_blueprint(): void
    {
        $product = $this->product('Policy', 'policy_point_newsletters');
        $user = StatamicUser::make()->id((string) Str::uuid())->email('blueprint-'.Str::random(8).'@example.test');
        $user->assignRole('scoped_operator');
        $user->save();

        try {
            $identity = app(StatamicUserIdentityBridgeInterface::class)->provision($user);
            ProductUserScope::query()->create([
                'user_id' => $identity->id, 'product_id' => $product->id,
                'scope_role' => 'product_operator', 'status' => 'active',
            ]);
            $collection = (new \Statamic\Entries\Collection)->handle($product->primary_collection_handle);
            $policy = app(ScopedEntryPolicy::class);

            app()->instance('request', Request::create('/cp/collections/policy_point_newsletters/entries/create/default?blueprint=policy-product'));
            $this->assertTrue($policy->create($user, $collection));
            app()->instance('request', Request::create('/cp/collections/policy_point_newsletters/entries/create/default?blueprint=wrong-product'));
            $this->assertFalse($policy->create($user, $collection));
            app()->instance('request', Request::create('/cp/collections/policy_point_newsletters/entries/default', 'POST', ['_blueprint' => 'policy-product']));
            $this->assertTrue($policy->store($user, $collection));
            app()->instance('request', Request::create('/cp/collections/policy_point_newsletters/entries/default', 'POST', ['_blueprint' => 'wrong-product']));
            $this->assertFalse($policy->store($user, $collection));
        } finally {
            $user->delete();
        }
    }

    public function test_scoped_operator_role_allows_cp_without_super_admin(): void
    {
        $role = StatamicRole::find('scoped_operator');

        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermission('access cp'));
        $this->assertFalse($role->isSuper());
    }

    public function test_organisation_operator_sees_only_owned_cp_data_and_direct_urls_are_guarded(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\Authorize::class);
        $owned = $this->product('Owned', 'foundation_newsletters');
        $foreign = $this->product('Foreign', 'insight_newsletters');
        $ownedCampaign = $this->campaign($owned);
        $foreignCampaign = $this->campaign($foreign);
        $mismatchedCampaign = Campaign::query()->create([
            'organisation_id' => $foreign->organisation_id, 'product_id' => $owned->id,
            'name' => 'Mismatched Legacy Campaign', 'collection' => $owned->primary_collection_handle,
            'subject' => 'Legacy', 'status' => 'draft',
        ]);
        $ownedGroup = $this->group($owned);
        $foreignGroup = $this->group($foreign);
        $ownedSubscriber = $this->subscriber($ownedGroup);
        $foreignSubscriber = $this->subscriber($foreignGroup);
        $ownedForm = $this->form($owned);
        $foreignForm = $this->form($foreign);
        $ownedSubscription = ProductForm::query()->create([
            'organisation_id' => $owned->organisation_id, 'product_id' => $owned->id,
            'form_scope' => 'product', 'name' => 'Owned Subscription', 'slug' => 'owned-subscription',
            'mode' => 'subscription', 'template_family' => 'subscription_basic', 'status' => 'draft',
            'field_definitions' => [['handle' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true]],
        ]);
        $foreignSubscription = ProductForm::query()->create([
            'organisation_id' => $foreign->organisation_id, 'product_id' => $foreign->id,
            'form_scope' => 'product', 'name' => 'Foreign Subscription', 'slug' => 'foreign-subscription',
            'mode' => 'subscription', 'template_family' => 'subscription_basic', 'status' => 'draft',
            'field_definitions' => [['handle' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true]],
        ]);
        EntryModel::query()->create([
            'site' => 'default', 'collection' => $owned->primary_collection_handle,
            'blueprint' => $owned->blueprint_handle, 'slug' => 'owned-newsletter',
            'date' => '2026-08-01', 'published' => true, 'data' => ['title' => 'Owned Newsletter Issue'],
        ]);
        EntryModel::query()->create([
            'site' => 'default', 'collection' => $foreign->primary_collection_handle,
            'blueprint' => $foreign->blueprint_handle, 'slug' => 'foreign-newsletter',
            'date' => '2026-08-02', 'published' => true, 'data' => ['title' => 'Foreign Newsletter Issue'],
        ]);

        $user = StatamicUser::make()->id((string) Str::uuid())->email('scoped-operator-'.Str::random(8).'@example.test');
        $user->assignRole('scoped_operator');
        $user->save();

        try {
            $identity = app(StatamicUserIdentityBridgeInterface::class)->provision($user);
            OrganisationUserScope::query()->create([
                'user_id' => $identity->id,
                'organisation_id' => $owned->organisation_id,
                'scope_role' => 'organisation_operator',
                'status' => 'active',
            ]);

            $this->actingAs($user, config('statamic.users.guards.cp', 'web'));
            $scope = app(CpOperatorScope::class);
            $this->assertTrue($scope->canCampaign($user, $ownedCampaign));
            $this->assertFalse($scope->canCampaign($user, $foreignCampaign));
            $this->assertFalse($scope->canCampaign($user, $mismatchedCampaign));
            $this->assertTrue($scope->canSubscriber($user, $ownedSubscriber));
            $this->assertFalse($scope->canSubscriber($user, $foreignSubscriber));

            $this->get(cp_route('newsletter.entries.create'))->assertOk()
                ->assertSee($owned->name)->assertDontSee($foreign->name);
            $this->get(cp_route('newsletter.entries.index'))->assertOk()
                ->assertSee('Owned Newsletter Issue')->assertDontSee('Foreign Newsletter Issue')
                ->assertSee($owned->name)->assertDontSee($foreign->name);

            $blueprint = Blueprint::make()->setNamespace('collections.'.$owned->primary_collection_handle);
            EntryBlueprintFound::dispatch($blueprint);
            $this->assertTrue($blueprint->hasField('newsletter_product_label'));
            $this->assertTrue($blueprint->columns()->get('newsletter_product_label')->visible());

            $this->get(cp_route('dashboard'))->assertOk()->assertInertia(function (AssertableInertia $page) use ($owned, $foreign): void {
                $page->component('Dashboard', false)->has('widgets', 1)
                    ->where('widgets.0.html', function (string $html) use ($owned, $foreign): bool {
                        return str_contains($html, $owned->organisation->name)
                            && str_contains($html, $owned->name)
                            && str_contains($html, 'Organisation workspace')
                            && ! str_contains($html, $foreign->organisation->name)
                            && ! str_contains($html, $foreign->name);
                    });
            });

            $this->get(cp_route('newsletter.campaigns.index'))->assertOk()->assertSee($ownedCampaign->name)
                ->assertDontSee($foreignCampaign->name)->assertDontSee($mismatchedCampaign->name);
            $this->get(cp_route('newsletter.campaigns.show', $foreignCampaign))->assertForbidden();
            $this->get(cp_route('newsletter.campaigns.show', $mismatchedCampaign))->assertForbidden();
            $this->get(cp_route('newsletter.analytics.campaign', $foreignCampaign))->assertForbidden();
            $this->get(cp_route('newsletter.subscribers.index'))->assertOk()->assertSee($ownedSubscriber->email)->assertDontSee($foreignSubscriber->email);
            $this->get(cp_route('newsletter.subscribers.show', $foreignSubscriber))->assertForbidden();
            $this->get(cp_route('newsletter.subscribers.gdpr.export', $ownedSubscriber))->assertForbidden();
            $this->get(cp_route('newsletter.analytics.webhooks'))->assertForbidden();
            $this->get(cp_route('newsletter.groups.index'))->assertOk()->assertSee($ownedGroup->name)->assertDontSee($foreignGroup->name);
            $this->get(cp_route('product-forms.index'))->assertRedirect(cp_route('subscription-forms.index'));
            $this->get(cp_route('product-forms.edit', $ownedForm))->assertForbidden();
            $this->get(cp_route('subscription-forms.index'))->assertOk()
                ->assertSee($ownedSubscription->name)->assertDontSee($foreignSubscription->name)
                ->assertDontSee($ownedForm->name)->assertDontSee($foreignForm->name);
            $this->get(cp_route('subscription-forms.edit', $foreignSubscription))->assertForbidden();
            $this->get(cp_route('subscription-forms.edit', $ownedForm))->assertForbidden();
            $this->get(cp_route('product-forms.edit', $foreignForm))->assertForbidden();
            $this->get(cp_route('organisation-domains.index'))->assertOk()->assertSee($owned->organisation->name)->assertDontSee($foreign->organisation->name);
            $this->get(cp_route('product-banners.index'))->assertOk()->assertSee($owned->name)->assertDontSee($foreign->name);
            $this->put(cp_route('product-banners.update', $foreign), ['remove_banner' => true])->assertForbidden();
            $this->getJson(cp_route('product-banners.default', [
                'collection' => $foreign->primary_collection_handle,
                'blueprint' => $foreign->blueprint_handle,
            ]))->assertForbidden();
            $this->postJson(cp_route('product-banners.upload-override'), [
                'collection' => $foreign->primary_collection_handle,
                'blueprint' => $foreign->blueprint_handle,
                'banner' => \Illuminate\Http\UploadedFile::fake()->image('foreign.png'),
            ])->assertForbidden();
            $this->get(cp_route('operator-scopes.index'))->assertForbidden();
            $this->assertFalse($scope->canCollection($user, 'pages'));

            $foreignSubGroup = $foreignGroup->subGroups()->first();
            $ownedSubscriber->subGroups()->attach($foreignSubGroup->id, ['subscribed_at' => now()]);
            $this->get(cp_route('newsletter.subscribers.edit', $ownedSubscriber))->assertForbidden();

            $admin = StatamicUser::findByEmail('admin@mailserver.test');
            $this->actingAs($admin, config('statamic.users.guards.cp', 'web'));
            $this->get(cp_route('operator-scopes.index'))->assertOk()->assertSee($user->email());
            $this->put(cp_route('operator-scopes.update', $user->id()), [
                'organisation_ids' => [$foreign->organisation_id],
                'product_ids' => [],
            ])->assertRedirect();

            $this->assertFalse($scope->canCampaign($user, $ownedCampaign));
            $this->assertTrue($scope->canCampaign($user, $foreignCampaign));

            $this->put(cp_route('operator-scopes.update', $user->id()), [
                'organisation_ids' => [$foreign->organisation_id],
                'product_ids' => [$foreign->id],
            ])->assertRedirect();
            $this->assertSame(0, ProductUserScope::query()->where('user_id', $identity->id)
                ->where('product_id', $foreign->id)->where('status', 'active')->count());
            $accessPage = $this->get(cp_route('operator-scopes.index'))->assertOk();
            $this->assertMatchesRegularExpression(
                '/data-products-for-organisation=.{0,100}hidden/s',
                $accessPage->getContent(),
            );

            $this->put(cp_route('operator-scopes.update', $user->id()), [
                'organisation_ids' => [],
                'product_ids' => [$owned->id],
            ])->assertRedirect();
            $this->assertSame(['owned-product'], $scope->blueprintHandlesForCollection($user, 'foundation_newsletters'));
            $this->assertTrue($scope->canEntry($user, 'foundation_newsletters', 'owned-product'));
            $this->assertFalse($scope->canEntry($user, 'foundation_newsletters', 'another-product'));

            $this->put(cp_route('operator-scopes.update', $user->id()), [
                'organisation_ids' => [],
                'product_ids' => [],
            ])->assertRedirect();
            $this->assertSame([], $scope->productIds($user));
            $this->assertFalse($scope->canCampaign($user, $ownedCampaign));
            $this->actingAs($user, config('statamic.users.guards.cp', 'web'));
            $this->get(cp_route('dashboard'))->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Dashboard', false)
                ->where('widgets.0.html', fn (string $html) => str_contains($html, 'No active products assigned')
                    && ! str_contains($html, $owned->name)
                    && ! str_contains($html, $foreign->name)));
        } finally {
            $user->delete();
        }
    }

    private function product(string $name, string $collection): Product
    {
        $organisation = Organisation::query()->create(['name' => $name.' Organisation', 'slug' => strtolower($name), 'status' => 'active']);
        return Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => $name.' Product', 'slug' => strtolower($name).'-product',
            'status' => 'active', 'product_type' => 'newsletter', 'primary_collection_handle' => $collection,
            'blueprint_handle' => strtolower($name).'-product',
        ])->load('organisation');
    }

    private function campaign(Product $product): Campaign
    {
        return Campaign::query()->create([
            'organisation_id' => $product->organisation_id, 'product_id' => $product->id,
            'name' => $product->name.' Campaign', 'collection' => $product->primary_collection_handle,
            'subject' => 'Test', 'status' => 'draft',
        ]);
    }

    private function group(Product $product): SubscriberGroup
    {
        return SubscriberGroup::query()->create([
            'organisation_id' => $product->organisation_id, 'product_id' => $product->id,
            'name' => $product->name.' Group', 'slug' => $product->slug.'-group',
            'collection_handle' => $product->primary_collection_handle,
        ]);
    }

    private function subscriber(SubscriberGroup $group): Subscriber
    {
        $subgroup = SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $group->id, 'name' => 'Regular', 'slug' => 'regular',
        ]);
        $subscriber = Subscriber::query()->create(['email' => $group->slug.'@example.test', 'status' => 'active']);
        $subscriber->subGroups()->attach($subgroup->id, ['subscribed_at' => now()]);
        return $subscriber;
    }

    private function form(Product $product): ProductForm
    {
        return ProductForm::query()->create([
            'organisation_id' => $product->organisation_id, 'product_id' => $product->id,
            'form_scope' => 'product', 'name' => $product->name.' Form', 'slug' => $product->slug.'-form',
            'mode' => 'application', 'template_family' => 'application_basic', 'status' => 'draft',
            'field_definitions' => [],
        ]);
    }
}
