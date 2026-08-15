<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use App\Support\Platform\Ownership\CampaignOwnershipWriter;
use App\Support\Platform\Ownership\ProductOwnershipResolver;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use LogicException;
use PHPUnit\Framework\TestCase;

class CampaignOwnershipWriterTest extends TestCase
{
    private CampaignOwnershipWriter $writer;

    private ProductOwnershipResolver $ownership;

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

        Capsule::schema()->create('campaigns', function ($table) {
            $table->id();
            $table->foreignId('organisation_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('name');
            $table->string('collection')->nullable();
            $table->string('subject');
            $table->string('status')->default('draft');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->timestamps();
        });

        $this->ownership = new ProductOwnershipResolver;
        $this->writer = new CampaignOwnershipWriter($this->ownership);
    }

    public function test_assigns_product_and_organisation_when_creating_a_campaign(): void
    {
        $product = $this->createProduct('policy_point_newsletters');
        $resolvedProduct = $this->ownership->productForPrimaryCollection('policy_point_newsletters');

        $campaign = $this->writer->updateOrCreateForProduct(
            $resolvedProduct,
            ['name' => 'Local Analytics Demo'],
            [
                'collection' => 'policy_point_newsletters',
                'subject' => 'Local demo campaign',
                'status' => 'sent',
            ],
        );

        $this->assertSame($product->getKey(), $campaign->product_id);
        $this->assertSame($product->organisation_id, $campaign->organisation_id);
    }

    public function test_creates_a_new_campaign_with_explicit_product_ownership(): void
    {
        $product = $this->createProduct('policy_point_newsletters');

        $campaign = $this->writer->createForProduct($product, [
            'name' => 'CP Campaign',
            'collection' => 'policy_point_newsletters',
            'subject' => 'Scoped campaign',
        ]);

        $this->assertTrue($campaign->exists);
        $this->assertSame($product->getKey(), $campaign->product_id);
        $this->assertSame($product->organisation_id, $campaign->organisation_id);
    }

    public function test_updates_an_existing_campaign_without_changing_its_ownership(): void
    {
        $product = $this->createProduct('policy_point_newsletters');
        $campaign = Campaign::query()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'name' => 'Existing Campaign',
            'collection' => 'policy_point_newsletters',
            'subject' => 'Existing subject',
        ]);

        $updated = $this->writer->updateForProduct($product, $campaign, [
            'subject' => 'Updated subject',
        ]);

        $this->assertSame($campaign->getKey(), $updated->getKey());
        $this->assertSame($product->getKey(), $updated->product_id);
        $this->assertSame($product->organisation_id, $updated->organisation_id);
        $this->assertSame('Updated subject', $updated->subject);
    }

    public function test_direct_update_fails_before_mutation_when_campaign_ownership_conflicts(): void
    {
        $product = $this->createProduct('policy_point_newsletters', 'target-product');
        $otherProduct = $this->createProduct('other_newsletters', 'other-product');
        $campaign = Campaign::query()->create([
            'organisation_id' => $otherProduct->organisation_id,
            'product_id' => $otherProduct->getKey(),
            'name' => 'Existing Campaign',
            'collection' => 'other_newsletters',
            'subject' => 'Existing subject',
        ]);

        try {
            $this->writer->updateForProduct($product, $campaign, [
                'subject' => 'Changed subject',
            ]);
            $this->fail('Conflicting ownership should fail.');
        } catch (LogicException) {
            $campaign->refresh();

            $this->assertSame($otherProduct->getKey(), $campaign->product_id);
            $this->assertSame('Existing subject', $campaign->subject);
        }
    }

    public function test_assigns_ownership_to_an_existing_unowned_campaign(): void
    {
        $product = $this->createProduct('policy_point_newsletters');
        $existing = Campaign::query()->create([
            'name' => 'Local Analytics Demo',
            'collection' => 'policy_point_newsletters',
            'subject' => 'Existing campaign',
        ]);

        $campaign = $this->writer->updateOrCreateForProduct(
            $product,
            ['name' => 'Local Analytics Demo'],
            ['subject' => 'Updated campaign'],
        );

        $this->assertSame($existing->getKey(), $campaign->getKey());
        $this->assertSame($product->getKey(), $campaign->product_id);
        $this->assertSame($product->organisation_id, $campaign->organisation_id);
        $this->assertSame(1, Campaign::query()->count());
    }

    public function test_missing_product_context_fails_before_a_campaign_is_written(): void
    {
        try {
            $this->ownership->productForPrimaryCollection('policy_point_newsletters');
            $this->fail('Missing product context should fail.');
        } catch (ModelNotFoundException) {
            $this->assertSame(0, Campaign::query()->count());
        }
    }

    public function test_ambiguous_product_context_fails_before_a_campaign_is_written(): void
    {
        $this->createProduct('policy_point_newsletters', 'product-one');
        $this->createProduct('policy_point_newsletters', 'product-two');

        try {
            $this->ownership->productForPrimaryCollection('policy_point_newsletters');
            $this->fail('Ambiguous product context should fail.');
        } catch (MultipleRecordsFoundException) {
            $this->assertSame(0, Campaign::query()->count());
        }
    }

    public function test_existing_campaign_ownership_cannot_be_silently_reassigned(): void
    {
        $targetProduct = $this->createProduct('policy_point_newsletters', 'target-product');
        $existingProduct = $this->createProduct('other_newsletters', 'existing-product');
        $campaign = Campaign::query()->create([
            'organisation_id' => $existingProduct->organisation_id,
            'product_id' => $existingProduct->getKey(),
            'name' => 'Local Analytics Demo',
            'subject' => 'Existing campaign',
        ]);

        try {
            $this->writer->updateOrCreateForProduct(
                $targetProduct,
                ['name' => 'Local Analytics Demo'],
                ['subject' => 'Changed campaign'],
            );
            $this->fail('Conflicting ownership should fail.');
        } catch (LogicException) {
            $campaign->refresh();

            $this->assertSame($existingProduct->getKey(), $campaign->product_id);
            $this->assertSame('Existing campaign', $campaign->subject);
        }
    }

    private function createProduct(string $collectionHandle, string $slug = 'policy-point'): Product
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
