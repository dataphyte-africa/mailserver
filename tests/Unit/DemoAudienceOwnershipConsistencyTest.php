<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Support\Platform\Ownership\CampaignOwnershipWriter;
use App\Support\Platform\Ownership\ProductOwnershipResolver;
use App\Support\Platform\Ownership\SubscriberGroupOwnershipWriter;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\MultipleRecordsFoundException;
use LogicException;
use PHPUnit\Framework\TestCase;

class DemoAudienceOwnershipConsistencyTest extends TestCase
{
    private CampaignOwnershipWriter $campaigns;

    private ProductOwnershipResolver $ownership;

    private SubscriberGroupOwnershipWriter $subscriberGroups;

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

        $this->ownership = new ProductOwnershipResolver;
        $this->campaigns = new CampaignOwnershipWriter($this->ownership);
        $this->subscriberGroups = new SubscriberGroupOwnershipWriter($this->ownership);
    }

    public function test_demo_group_and_campaign_share_the_same_explicit_ownership(): void
    {
        $product = $this->createProduct('policy_point_newsletters');
        $resolvedProduct = $this->ownership->productForPrimaryCollection('policy_point_newsletters');

        $group = $this->subscriberGroups->updateOrCreateForProduct(
            $resolvedProduct,
            ['slug' => 'policy-point-subscribers'],
            [
                'name' => 'Policy Point Subscribers',
                'collection_handle' => 'policy_point_newsletters',
            ],
        );
        $campaign = $this->campaigns->updateOrCreateForProduct(
            $resolvedProduct,
            ['name' => 'Local Analytics Demo'],
            [
                'collection' => 'policy_point_newsletters',
                'subject' => 'Local demo campaign',
            ],
        );

        $this->assertSame($product->getKey(), $group->product_id);
        $this->assertSame($campaign->product_id, $group->product_id);
        $this->assertSame($campaign->organisation_id, $group->organisation_id);
    }

    public function test_missing_product_mapping_prevents_demo_group_and_campaign_writes(): void
    {
        try {
            $this->ownership->productForPrimaryCollection('policy_point_newsletters');
            $this->fail('Missing product context should fail.');
        } catch (ModelNotFoundException) {
            $this->assertNoDemoOwnershipWrites();
        }
    }

    public function test_duplicate_product_mapping_prevents_demo_group_and_campaign_writes(): void
    {
        $this->createProduct('policy_point_newsletters', 'product-one');
        $this->createProduct('policy_point_newsletters', 'product-two');

        try {
            $this->ownership->productForPrimaryCollection('policy_point_newsletters');
            $this->fail('Ambiguous product context should fail.');
        } catch (MultipleRecordsFoundException) {
            $this->assertNoDemoOwnershipWrites();
        }
    }

    public function test_conflicting_group_ownership_prevents_partial_demo_writes(): void
    {
        $targetProduct = $this->createProduct('policy_point_newsletters', 'target-product');
        $existingProduct = $this->createProduct('other_newsletters', 'existing-product');
        $group = SubscriberGroup::query()->create([
            'organisation_id' => $existingProduct->organisation_id,
            'product_id' => $existingProduct->getKey(),
            'name' => 'Policy Point Subscribers',
            'slug' => 'policy-point-subscribers',
        ]);

        try {
            Capsule::connection()->transaction(function () use ($targetProduct): void {
                $this->subscriberGroups->updateOrCreateForProduct(
                    $targetProduct,
                    ['slug' => 'policy-point-subscribers'],
                    ['name' => 'Changed group'],
                );

                $this->campaigns->updateOrCreateForProduct(
                    $targetProduct,
                    ['name' => 'Local Analytics Demo'],
                    ['subject' => 'Local demo campaign'],
                );
            });
            $this->fail('Conflicting group ownership should fail.');
        } catch (LogicException) {
            $group->refresh();

            $this->assertSame($existingProduct->getKey(), $group->product_id);
            $this->assertSame('Policy Point Subscribers', $group->name);
            $this->assertSame(0, Campaign::query()->count());
        }
    }

    private function assertNoDemoOwnershipWrites(): void
    {
        $this->assertSame(0, SubscriberGroup::query()->count());
        $this->assertSame(0, Campaign::query()->count());
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
