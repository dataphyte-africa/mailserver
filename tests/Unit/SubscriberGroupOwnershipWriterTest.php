<?php

namespace Tests\Unit;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Support\Platform\Ownership\ProductOwnershipResolver;
use App\Support\Platform\Ownership\SubscriberGroupOwnershipWriter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use LogicException;
use PHPUnit\Framework\TestCase;

class SubscriberGroupOwnershipWriterTest extends TestCase
{
    private SubscriberGroupOwnershipWriter $writer;

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
            $table->string('primary_collection_handle')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('subscriber_groups', function ($table) {
            $table->id();
            $table->foreignId('organisation_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('collection_handle')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $this->writer = new SubscriberGroupOwnershipWriter(new ProductOwnershipResolver);
    }

    public function test_assigns_product_and_organisation_when_creating_a_group(): void
    {
        $product = $this->createProduct('insight_newsletters');

        $resolvedProduct = $this->writer->productForPrimaryCollection('insight_newsletters');
        $group = $this->writer->updateOrCreateForProduct(
            $resolvedProduct,
            ['slug' => 'insight-subscribers'],
            [
                'name' => 'Insight Subscribers',
                'collection_handle' => 'insight_newsletters',
            ],
        );

        $this->assertSame($product->getKey(), $group->product_id);
        $this->assertSame($product->organisation_id, $group->organisation_id);
    }

    public function test_creates_a_group_with_explicit_product_ownership(): void
    {
        $product = $this->createProduct('insight_newsletters');

        $group = $this->writer->createForProduct($product, [
            'name' => 'CP Insight Group',
            'slug' => 'cp-insight-group',
            'collection_handle' => 'insight_newsletters',
        ]);

        $this->assertTrue($group->exists);
        $this->assertSame($product->getKey(), $group->product_id);
        $this->assertSame($product->organisation_id, $group->organisation_id);
    }

    public function test_updates_a_group_without_changing_its_ownership(): void
    {
        $product = $this->createProduct('insight_newsletters');
        $group = $this->writer->createForProduct($product, [
            'name' => 'Existing Group',
            'slug' => 'existing-group',
            'collection_handle' => 'insight_newsletters',
        ]);

        $updated = $this->writer->updateForProduct($product, $group, [
            'name' => 'Updated Group',
            'slug' => 'updated-group',
        ]);

        $this->assertSame($group->getKey(), $updated->getKey());
        $this->assertSame($product->getKey(), $updated->product_id);
        $this->assertSame($product->organisation_id, $updated->organisation_id);
        $this->assertSame('Updated Group', $updated->name);
    }

    public function test_direct_update_fails_before_mutation_for_conflicting_ownership(): void
    {
        $product = $this->createProduct('insight_newsletters', 'target-product');
        $otherProduct = $this->createProduct('other_newsletters', 'other-product');
        $group = SubscriberGroup::query()->create([
            'organisation_id' => $otherProduct->organisation_id,
            'product_id' => $otherProduct->getKey(),
            'name' => 'Existing Group',
            'slug' => 'existing-group',
            'collection_handle' => 'other_newsletters',
        ]);

        try {
            $this->writer->updateForProduct($product, $group, ['name' => 'Changed Group']);
            $this->fail('Conflicting ownership should fail.');
        } catch (LogicException) {
            $group->refresh();

            $this->assertSame($otherProduct->getKey(), $group->product_id);
            $this->assertSame('Existing Group', $group->name);
        }
    }

    public function test_assigns_ownership_to_the_existing_group_on_the_controlled_write(): void
    {
        $product = $this->createProduct('insight_newsletters');
        $existing = SubscriberGroup::query()->create([
            'name' => 'Existing Insight Subscribers',
            'slug' => 'insight-subscribers',
            'collection_handle' => 'insight_newsletters',
        ]);

        $group = $this->writer->updateOrCreateForProduct(
            $product,
            ['slug' => 'insight-subscribers'],
            ['name' => 'Insight Subscribers'],
        );

        $this->assertSame($existing->getKey(), $group->getKey());
        $this->assertSame($product->getKey(), $group->product_id);
        $this->assertSame($product->organisation_id, $group->organisation_id);
        $this->assertSame(1, SubscriberGroup::query()->count());
    }

    public function test_missing_product_context_fails_before_a_group_is_written(): void
    {
        try {
            $this->writer->productForPrimaryCollection('insight_newsletters');
            $this->fail('Missing product context should fail.');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, SubscriberGroup::query()->count());
        }
    }

    public function test_ambiguous_product_context_fails_before_a_group_is_written(): void
    {
        $this->createProduct('insight_newsletters', 'product-one');
        $this->createProduct('insight_newsletters', 'product-two');

        try {
            $this->writer->productForPrimaryCollection('insight_newsletters');
            $this->fail('Ambiguous product context should fail.');
        } catch (MultipleRecordsFoundException) {
            $this->assertSame(0, SubscriberGroup::query()->count());
        }
    }

    public function test_existing_ownership_cannot_be_silently_reassigned(): void
    {
        $targetProduct = $this->createProduct('insight_newsletters', 'target-product');
        $existingProduct = $this->createProduct('other_newsletters', 'existing-product');
        $group = SubscriberGroup::query()->create([
            'organisation_id' => $existingProduct->organisation_id,
            'product_id' => $existingProduct->getKey(),
            'name' => 'Existing group',
            'slug' => 'insight-subscribers',
        ]);

        try {
            $this->writer->updateOrCreateForProduct(
                $targetProduct,
                ['slug' => 'insight-subscribers'],
                ['name' => 'Changed group'],
            );
            $this->fail('Conflicting ownership should fail.');
        } catch (LogicException) {
            $group->refresh();

            $this->assertSame($existingProduct->getKey(), $group->product_id);
            $this->assertSame('Existing group', $group->name);
        }
    }

    private function createProduct(string $collectionHandle, string $slug = 'insight'): Product
    {
        $organisation = Organisation::query()->create([
            'name' => 'Organisation '.$slug,
            'slug' => 'organisation-'.$slug,
        ]);

        return Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Product '.$slug,
            'slug' => $slug,
            'primary_collection_handle' => $collectionHandle,
        ]);
    }
}
