<?php

namespace Tests\Unit;

use App\Exceptions\Newsletter\CampaignAudienceOwnershipException;
use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\CampaignAudienceOwnershipService;
use App\Support\Platform\Ownership\CampaignOwnershipWriter;
use App\Support\Platform\Ownership\ProductOwnershipResolver;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\TestCase;

class CampaignAudienceOwnershipServiceTest extends TestCase
{
    private CampaignAudienceOwnershipService $service;

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

        Capsule::schema()->create('subscriber_groups', function ($table) {
            $table->id();
            $table->foreignId('organisation_id')->nullable();
            $table->foreignId('product_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('collection_handle')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('subscriber_sub_groups', function ($table) {
            $table->id();
            $table->foreignId('subscriber_group_id');
            $table->string('name');
            $table->string('slug');
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable();
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

        Capsule::schema()->create('campaign_audiences', function ($table) {
            $table->id();
            $table->foreignId('campaign_id');
            $table->string('targetable_type');
            $table->unsignedBigInteger('targetable_id');
            $table->boolean('send_to_all')->default(false);
            $table->timestamps();
        });

        Relation::morphMap([
            'subscriber_group' => SubscriberGroup::class,
            'subscriber_sub_group' => SubscriberSubGroup::class,
        ]);

        $this->service = new CampaignAudienceOwnershipService;
    }

    public function test_validates_and_assigns_send_to_all_within_the_campaign_product(): void
    {
        $product = $this->createProduct('product-one');
        $group = $this->createGroup($product, 'group-one');
        $campaign = $this->createCampaign($product);

        $audience = $this->service->validateForProduct($product, true, []);
        $this->service->replace($campaign, $audience);

        $row = CampaignAudience::query()->sole();
        $this->assertTrue($audience->sendsToAll());
        $this->assertSame('subscriber_group', $row->targetable_type);
        $this->assertSame($group->getKey(), $row->targetable_id);
        $this->assertTrue($row->send_to_all);
    }

    public function test_send_to_all_fails_when_group_ownership_is_missing(): void
    {
        $product = $this->createProduct('product-one');
        SubscriberGroup::query()->create([
            'name' => 'Legacy group',
            'slug' => 'legacy-group',
            'collection_handle' => $product->primary_collection_handle,
        ]);

        $this->expectOwnershipFailure('send_to_all', function () use ($product) {
            $this->service->validateForProduct($product, true, []);
        });
    }

    public function test_send_to_all_fails_when_multiple_owned_groups_are_ambiguous(): void
    {
        $product = $this->createProduct('product-one');
        $this->createGroup($product, 'group-one');
        $this->createGroup($product, 'group-two');

        $this->expectOwnershipFailure('send_to_all', function () use ($product) {
            $this->service->validateForProduct($product, true, []);
        });
    }

    public function test_validates_and_assigns_subgroups_inherited_from_the_product_group(): void
    {
        $product = $this->createProduct('product-one');
        $group = $this->createGroup($product, 'group-one');
        $first = $this->createSubGroup($group, 'first');
        $second = $this->createSubGroup($group, 'second');
        $campaign = $this->createCampaign($product);

        $audience = $this->service->validateForProduct($product, false, [$second->id, $first->id]);
        $this->service->replace($campaign, $audience);

        $this->assertFalse($audience->sendsToAll());
        $this->assertSame([$second->id, $first->id], $audience->subGroups->modelKeys());
        $this->assertSame(
            [$first->id, $second->id],
            CampaignAudience::query()->orderBy('targetable_id')->pluck('targetable_id')->all(),
        );
        $this->assertSame(
            ['subscriber_sub_group'],
            CampaignAudience::query()->distinct()->pluck('targetable_type')->all(),
        );
    }

    public function test_subgroup_selection_fails_for_missing_or_cross_product_ownership(): void
    {
        $product = $this->createProduct('product-one');
        $otherProduct = $this->createProduct('product-two');
        $otherGroup = $this->createGroup($otherProduct, 'other-group');
        $otherSubGroup = $this->createSubGroup($otherGroup, 'other-subgroup');

        $this->expectOwnershipFailure('sub_groups', function () use ($otherSubGroup, $product) {
            $this->service->validateForProduct($product, false, [$otherSubGroup->id]);
        });

        $this->expectOwnershipFailure('sub_groups', function () use ($product) {
            $this->service->validateForProduct($product, false, [99999]);
        });
    }

    public function test_subgroup_selection_fails_when_parent_group_ownership_is_missing(): void
    {
        $product = $this->createProduct('product-one');
        $group = SubscriberGroup::query()->create([
            'name' => 'Legacy group',
            'slug' => 'legacy-group',
            'collection_handle' => $product->primary_collection_handle,
        ]);
        $subGroup = $this->createSubGroup($group, 'legacy-subgroup');

        $this->expectOwnershipFailure('sub_groups', function () use ($product, $subGroup) {
            $this->service->validateForProduct($product, false, [$subGroup->id]);
        });
    }

    public function test_archived_audience_structures_are_not_selectable_for_new_campaign_targeting(): void
    {
        $product = $this->createProduct('product-one');
        $group = $this->createGroup($product, 'group-one');
        $subGroup = $this->createSubGroup($group, 'subgroup-one');

        $group->forceFill(['archived_at' => '2026-07-31 00:00:00'])->save();

        $this->expectOwnershipFailure('send_to_all', function () use ($product) {
            $this->service->validateForProduct($product, true, []);
        });

        $group->forceFill(['archived_at' => null])->save();
        $subGroup->forceFill(['archived_at' => '2026-07-31 00:00:00'])->save();

        $this->expectOwnershipFailure('sub_groups', function () use ($product, $subGroup) {
            $this->service->validateForProduct($product, false, [$subGroup->getKey()]);
        });
    }

    public function test_archiving_does_not_remove_historical_campaign_audience_rows(): void
    {
        $product = $this->createProduct('product-one');
        $group = $this->createGroup($product, 'group-one');
        $subGroup = $this->createSubGroup($group, 'subgroup-one');
        $campaign = $this->createCampaign($product);
        $audience = $this->service->validateForProduct($product, false, [$subGroup->getKey()]);
        $this->service->replace($campaign, $audience);

        $subGroup->forceFill(['archived_at' => '2026-07-31 00:00:00'])->save();

        $this->assertSame(1, CampaignAudience::query()->count());
        $this->assertSame($subGroup->getKey(), CampaignAudience::query()->sole()->targetable_id);
    }

    public function test_inactive_product_or_organisation_fails_before_audience_assignment(): void
    {
        $inactiveProduct = $this->createProduct('inactive-product', 'inactive');

        $this->expectOwnershipFailure('product_id', function () use ($inactiveProduct) {
            $this->service->validateForProduct($inactiveProduct, false, []);
        });

        $product = $this->createProduct('inactive-organisation-product');
        $product->organisation->update(['status' => 'inactive']);
        $product->unsetRelation('organisation');

        $this->expectOwnershipFailure('product_id', function () use ($product) {
            $this->service->validateForProduct($product, false, []);
        });
    }

    public function test_in_scope_campaign_update_and_audience_assignment_succeed_together(): void
    {
        $product = $this->createProduct('product-one');
        $group = $this->createGroup($product, 'group-one');
        $subGroup = $this->createSubGroup($group, 'subgroup-one');
        $campaign = $this->createCampaign($product);
        $writer = new CampaignOwnershipWriter(new ProductOwnershipResolver);

        $audience = $this->service->validateForProduct($product, false, [$subGroup->id]);
        $campaign = $writer->updateForProduct($product, $campaign, [
            'subject' => 'Updated subject',
        ]);
        $this->service->replace($campaign, $audience);

        $this->assertSame('Updated subject', $campaign->subject);
        $this->assertSame($subGroup->getKey(), CampaignAudience::query()->sole()->targetable_id);
    }

    public function test_cross_product_audience_change_fails_before_campaign_update(): void
    {
        $product = $this->createProduct('product-one');
        $otherProduct = $this->createProduct('product-two');
        $otherGroup = $this->createGroup($otherProduct, 'other-group');
        $otherSubGroup = $this->createSubGroup($otherGroup, 'other-subgroup');
        $campaign = $this->createCampaign($product);
        $writer = new CampaignOwnershipWriter(new ProductOwnershipResolver);

        try {
            $audience = $this->service->validateForProduct($product, false, [$otherSubGroup->id]);
            $writer->updateForProduct($product, $campaign, ['subject' => 'Changed subject']);
            $this->service->replace($campaign, $audience);
            $this->fail('Cross-product audience validation should fail before campaign update.');
        } catch (CampaignAudienceOwnershipException $exception) {
            $campaign->refresh();

            $this->assertSame('sub_groups', $exception->input());
            $this->assertSame('Subject', $campaign->subject);
            $this->assertSame(0, CampaignAudience::query()->count());
        }
    }

    public function test_validates_a_persisted_in_product_subgroup_audience_for_edit(): void
    {
        $product = $this->createProduct('product-one');
        $group = $this->createGroup($product, 'group-one');
        $subGroup = $this->createSubGroup($group, 'subgroup-one');
        $campaign = $this->createCampaign($product);
        CampaignAudience::query()->create([
            'campaign_id' => $campaign->getKey(),
            'targetable_type' => $subGroup->getMorphClass(),
            'targetable_id' => $subGroup->getKey(),
            'send_to_all' => false,
        ]);

        $audience = $this->service->validatePersistedForProduct($campaign, $product);

        $this->assertFalse($audience->sendsToAll());
        $this->assertSame([$subGroup->getKey()], $audience->subGroups->modelKeys());
    }

    public function test_persisted_cross_product_send_to_all_audience_fails_closed_for_edit(): void
    {
        $product = $this->createProduct('product-one');
        $this->createGroup($product, 'group-one');
        $otherProduct = $this->createProduct('product-two');
        $otherGroup = $this->createGroup($otherProduct, 'other-group');
        $campaign = $this->createCampaign($product);
        CampaignAudience::query()->create([
            'campaign_id' => $campaign->getKey(),
            'targetable_type' => $otherGroup->getMorphClass(),
            'targetable_id' => $otherGroup->getKey(),
            'send_to_all' => true,
        ]);

        $this->expectOwnershipFailure('send_to_all', function () use ($campaign, $product) {
            $this->service->validatePersistedForProduct($campaign, $product);
        });
    }

    private function createProduct(string $slug, string $status = 'active'): Product
    {
        $organisation = Organisation::query()->create([
            'name' => $slug.' organisation',
            'slug' => $slug.'-organisation',
            'status' => 'active',
        ]);

        return Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => $slug,
            'slug' => $slug,
            'status' => $status,
            'primary_collection_handle' => $slug.'_newsletters',
        ]);
    }

    private function createGroup(Product $product, string $slug): SubscriberGroup
    {
        return SubscriberGroup::query()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'name' => $slug,
            'slug' => $slug,
            'collection_handle' => $product->primary_collection_handle,
        ]);
    }

    private function createSubGroup(SubscriberGroup $group, string $slug): SubscriberSubGroup
    {
        return SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $group->getKey(),
            'name' => $slug,
            'slug' => $slug,
        ]);
    }

    private function createCampaign(Product $product): Campaign
    {
        return Campaign::query()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'name' => 'Campaign',
            'collection' => $product->primary_collection_handle,
            'subject' => 'Subject',
        ]);
    }

    private function expectOwnershipFailure(string $input, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Audience ownership validation should fail.');
        } catch (CampaignAudienceOwnershipException $exception) {
            $this->assertSame($input, $exception->input());
        }
    }
}
