<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductUserScope;
use App\Models\User;
use App\Services\Newsletter\ScopedCampaignProductSelector;
use App\Support\Platform\Authorization\ScopeResolver;
use App\Support\Platform\Authorization\StatamicUserIdentityBridge;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Statamic\Contracts\Auth\User as StatamicUser;

class ScopedCampaignProductSelectorTest extends TestCase
{
    private ScopedCampaignProductSelector $selector;

    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule;
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('users', function ($table) {
            $table->id();
            $table->string('statamic_user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('organisations', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Capsule::schema()->create('products', function ($table) {
            $table->id();
            $table->foreignId('organisation_id');
            $table->string('name');
            $table->string('slug');
            $table->string('status')->default('active');
            $table->string('primary_collection_handle')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('campaigns', function ($table) {
            $table->id();
            $table->foreignId('organisation_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('name');
            $table->string('collection')->nullable();
            $table->string('subject');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Capsule::schema()->create('product_user_scope', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('product_id');
            $table->string('scope_role');
            $table->string('status')->default('active');
            $table->foreignId('granted_by')->nullable();
            $table->timestamps();
        });

        $this->selector = new ScopedCampaignProductSelector(
            new StatamicUserIdentityBridge,
            new ScopeResolver('active'),
        );
    }

    public function test_lists_only_active_products_in_the_linked_operators_active_scope(): void
    {
        $user = $this->createUser('statamic-123');
        $allowed = $this->createProduct('Allowed', 'allowed', 'allowed_newsletters');
        $inactiveScope = $this->createProduct('Inactive Scope', 'inactive-scope', 'inactive_newsletters');
        $inactiveProduct = $this->createProduct('Inactive Product', 'inactive-product', 'disabled_newsletters', 'inactive');
        $unscoped = $this->createProduct('Unscoped', 'unscoped', 'unscoped_newsletters');

        $this->scope($user, $allowed);
        $this->scope($user, $inactiveScope, 'inactive');
        $this->scope($user, $inactiveProduct);

        $products = $this->selector->productsFor($this->statamicUser('statamic-123'));

        $this->assertSame([$allowed->getKey()], $products->modelKeys());
        $this->assertNotContains($unscoped->getKey(), $products->modelKeys());
    }

    public function test_super_user_can_list_active_newsletter_products_without_relational_scope_rows(): void
    {
        $allowed = $this->createProduct('Allowed', 'allowed', 'allowed_newsletters');
        $inactiveProduct = $this->createProduct('Inactive Product', 'inactive-product', 'disabled_newsletters', 'inactive');
        $noCollection = $this->createProduct('No Collection', 'no-collection', '');

        $products = $this->selector->productsFor($this->statamicUser('super-user', true));

        $this->assertSame([$allowed->getKey()], $products->modelKeys());
        $this->assertNotContains($inactiveProduct->getKey(), $products->modelKeys());
        $this->assertNotContains($noCollection->getKey(), $products->modelKeys());
    }

    public function test_resolves_a_scoped_active_product_for_its_primary_collection(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('Allowed', 'allowed', 'allowed_newsletters');
        $this->scope($user, $product);

        $resolved = $this->selector->resolve(
            $this->statamicUser('statamic-123'),
            $product->getKey(),
            'allowed_newsletters',
        );

        $this->assertSame($product->getKey(), $resolved?->getKey());
        $this->assertSame($product->organisation_id, $resolved?->organisation?->getKey());
    }

    public function test_fails_closed_when_operator_identity_is_missing(): void
    {
        $product = $this->createProduct('Allowed', 'allowed', 'allowed_newsletters');

        $this->assertNull($this->selector->resolve(
            $this->statamicUser('missing-link'),
            $product->getKey(),
            'allowed_newsletters',
        ));
        $this->assertTrue($this->selector->productsFor($this->statamicUser('missing-link'))->isEmpty());
    }

    public function test_fails_closed_for_unscoped_or_collection_conflicting_products(): void
    {
        $user = $this->createUser('statamic-123');
        $scoped = $this->createProduct('Scoped', 'scoped', 'scoped_newsletters');
        $unscoped = $this->createProduct('Unscoped', 'unscoped', 'unscoped_newsletters');
        $this->scope($user, $scoped);
        $operator = $this->statamicUser('statamic-123');

        $this->assertNull($this->selector->resolve($operator, $unscoped->getKey(), 'unscoped_newsletters'));
        $this->assertNull($this->selector->resolve($operator, $scoped->getKey(), 'other_newsletters'));
    }

    public function test_resolves_an_owned_campaign_inside_the_operators_active_product_scope(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('Allowed', 'allowed', 'allowed_newsletters');
        $campaign = $this->createCampaign($product);
        $this->scope($user, $product);

        $resolved = $this->selector->resolveCampaign(
            $this->statamicUser('statamic-123'),
            $campaign,
        );

        $this->assertSame($product->getKey(), $resolved?->getKey());
    }

    public function test_campaign_resolution_fails_closed_for_missing_or_conflicting_ownership(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('Allowed', 'allowed', 'allowed_newsletters');
        $otherProduct = $this->createProduct('Other', 'other', 'other_newsletters');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $unowned = Campaign::query()->create([
            'name' => 'Unowned',
            'collection' => $product->primary_collection_handle,
            'subject' => 'Unowned campaign',
        ]);
        $conflictingOrganisation = $this->createCampaign($product, $otherProduct->organisation_id);
        $wrongCollection = $this->createCampaign($product);
        $wrongCollection->update(['collection' => 'other_newsletters']);

        $this->assertNull($this->selector->resolveCampaign($operator, $unowned));
        $this->assertNull($this->selector->resolveCampaign($operator, $conflictingOrganisation));
        $this->assertNull($this->selector->resolveCampaign($operator, $wrongCollection));
        $this->assertNull($this->selector->resolveCampaign(
            $operator,
            $this->createCampaign($otherProduct),
        ));
    }

    public function test_campaign_resolution_rechecks_collection_and_active_organisation(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('Allowed', 'allowed', 'allowed_newsletters');
        $campaign = $this->createCampaign($product);
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $this->assertNull($this->selector->resolveCampaign($operator, $campaign, 'other_newsletters'));

        $product->organisation->update(['status' => 'inactive']);

        $this->assertNull($this->selector->resolveCampaign($operator, $campaign));
    }

    private function createUser(string $statamicUserId): User
    {
        return User::query()->create([
            'statamic_user_id' => $statamicUserId,
            'name' => 'Operator',
            'email' => $statamicUserId.'@example.com',
        ]);
    }

    private function createProduct(
        string $name,
        string $slug,
        string $collectionHandle,
        string $status = 'active',
    ): Product {
        $organisation = Organisation::query()->create([
            'name' => $name.' Organisation',
            'slug' => $slug.'-organisation',
        ]);

        return Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => $name,
            'slug' => $slug,
            'status' => $status,
            'primary_collection_handle' => $collectionHandle,
        ]);
    }

    private function scope(User $user, Product $product, string $status = 'active'): void
    {
        ProductUserScope::query()->create([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
            'scope_role' => 'product_manager',
            'status' => $status,
        ]);
    }

    private function createCampaign(Product $product, ?int $organisationId = null): Campaign
    {
        return Campaign::query()->create([
            'organisation_id' => $organisationId ?? $product->organisation_id,
            'product_id' => $product->getKey(),
            'name' => 'Campaign '.$product->slug,
            'collection' => $product->primary_collection_handle,
            'subject' => 'Campaign subject',
        ]);
    }

    /**
     * @return StatamicUser&Stub
     */
    private function statamicUser(string $id, bool $super = false): StatamicUser
    {
        $user = $this->createStub(StatamicUser::class);
        $user->method('getAuthIdentifier')->willReturn($id);
        $user->method('isSuper')->willReturn($super);

        return $user;
    }
}
