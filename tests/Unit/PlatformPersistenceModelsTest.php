<?php

namespace Tests\Unit;

use App\Models\Organisation;
use App\Models\OrganisationUserScope;
use App\Models\Product;
use App\Models\ProductUserScope;
use App\Models\StatamicGroupScopeMap;
use App\Models\User;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

class PlatformPersistenceModelsTest extends TestCase
{
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
    }

    public function test_organisation_model_uses_expected_table_and_casts(): void
    {
        $organisation = new Organisation;

        $this->assertSame('organisations', $organisation->getTable());
        $this->assertSame('array', $organisation->getCasts()['compliance_profile']);
    }

    public function test_product_model_tracks_domain_and_sender_fields(): void
    {
        $product = new Product;

        $this->assertSame('products', $product->getTable());
        $this->assertSame('boolean', $product->getCasts()['domain_is_primary']);
        $this->assertSame('array', $product->getCasts()['default_sender_profile']);
        $this->assertSame('boolean', $product->getCasts()['fallback_to_platform_domain']);
    }

    public function test_user_has_relational_scope_links(): void
    {
        $user = new User;

        $this->assertInstanceOf(HasMany::class, $user->organisationScopes());
        $this->assertInstanceOf(HasMany::class, $user->productScopes());
        $this->assertSame(OrganisationUserScope::class, $user->organisationScopes()->getRelated()::class);
        $this->assertSame(ProductUserScope::class, $user->productScopes()->getRelated()::class);
    }

    public function test_scope_models_point_to_expected_foundation_records(): void
    {
        $organisationScope = new OrganisationUserScope;
        $productScope = new ProductUserScope;

        $this->assertSame('organisation_user_scope', $organisationScope->getTable());
        $this->assertSame('product_user_scope', $productScope->getTable());
        $this->assertInstanceOf(BelongsTo::class, $organisationScope->organisation());
        $this->assertInstanceOf(BelongsTo::class, $productScope->product());
        $this->assertSame(Organisation::class, $organisationScope->organisation()->getRelated()::class);
        $this->assertSame(Product::class, $productScope->product()->getRelated()::class);
    }

    public function test_statamic_group_scope_map_stays_optional_foundation_helper(): void
    {
        $map = new StatamicGroupScopeMap;

        $this->assertSame('statamic_group_scope_map', $map->getTable());
        $this->assertInstanceOf(BelongsTo::class, $map->organisation());
        $this->assertInstanceOf(BelongsTo::class, $map->product());
    }
}
