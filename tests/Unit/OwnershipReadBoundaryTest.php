<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\EmailTemplate;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class OwnershipReadBoundaryTest extends TestCase
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

        Capsule::schema()->create('organisations', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        Capsule::schema()->create('products', function ($table) {
            $table->id();
            $table->foreignId('organisation_id');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        foreach (['subscriber_groups', 'campaigns', 'email_templates'] as $tableName) {
            Capsule::schema()->create($tableName, function ($table) {
                $table->id();
                $table->foreignId('organisation_id')->nullable();
                $table->foreignId('product_id')->nullable();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function test_product_owned_records_share_explicit_ownership_scopes(): void
    {
        foreach ($this->productOwnedModels() as $modelClass) {
            $productOne = $modelClass::query()->create([
                'organisation_id' => 10,
                'product_id' => 100,
                'name' => 'Product one record',
            ]);
            $productTwo = $modelClass::query()->create([
                'organisation_id' => 10,
                'product_id' => 200,
                'name' => 'Product two record',
            ]);
            $otherOrganisation = $modelClass::query()->create([
                'organisation_id' => 20,
                'product_id' => 300,
                'name' => 'Other organisation record',
            ]);
            $legacyUnowned = $modelClass::query()->create([
                'name' => 'Legacy unowned record',
            ]);

            $this->assertSame(
                [$productOne->getKey()],
                $modelClass::query()->ownedByProduct(100)->pluck('id')->all(),
            );
            $this->assertSame(
                [$productOne->getKey(), $otherOrganisation->getKey()],
                $modelClass::query()->ownedByProducts([100, 300])->pluck('id')->all(),
            );
            $this->assertSame(
                [$productOne->getKey(), $productTwo->getKey()],
                $modelClass::query()->ownedByOrganisation(10)->pluck('id')->all(),
            );
            $this->assertSame(
                [$productTwo->getKey()],
                $modelClass::query()->withinOwnership(10, 200)->pluck('id')->all(),
            );
            $this->assertNotContains(
                $legacyUnowned->getKey(),
                $modelClass::query()->ownedByOrganisation(10)->pluck('id')->all(),
            );
        }
    }

    public function test_empty_allowed_product_set_fails_closed(): void
    {
        foreach ($this->productOwnedModels() as $modelClass) {
            $modelClass::query()->create([
                'organisation_id' => 10,
                'product_id' => 100,
                'name' => 'Owned record',
            ]);

            $this->assertSame(
                [],
                $modelClass::query()->ownedByProducts([])->pluck('id')->all(),
            );
        }
    }

    public function test_scopes_accept_persisted_ownership_models(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Organisation',
            'slug' => 'organisation',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Product',
            'slug' => 'product',
        ]);

        $record = SubscriberGroup::query()->create([
            'organisation_id' => $organisation->getKey(),
            'product_id' => $product->getKey(),
            'name' => 'Owned record',
        ]);

        $this->assertSame(
            [$record->getKey()],
            SubscriberGroup::query()->withinOwnership($organisation, $product)->pluck('id')->all(),
        );
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function productOwnedModels(): array
    {
        return [
            SubscriberGroup::class,
            Campaign::class,
            EmailTemplate::class,
        ];
    }
}
