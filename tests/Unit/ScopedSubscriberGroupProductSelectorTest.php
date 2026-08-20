<?php

namespace Tests\Unit;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductUserScope;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Models\User;
use App\Services\Newsletter\ScopedCampaignProductSelector;
use App\Services\Newsletter\ScopedSubscriberGroupDeletionService;
use App\Services\Newsletter\ScopedSubscriberGroupProductSelector;
use App\Support\Platform\Authorization\ScopeResolver;
use App\Support\Platform\Authorization\StatamicUserIdentityBridge;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Statamic\Contracts\Auth\User as StatamicUser;

class ScopedSubscriberGroupProductSelectorTest extends TestCase
{
    private ScopedSubscriberGroupProductSelector $selector;

    private ScopedSubscriberGroupDeletionService $deletions;

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

        Capsule::schema()->create('product_user_scope', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('product_id');
            $table->string('scope_role');
            $table->string('status')->default('active');
            $table->foreignId('granted_by')->nullable();
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

        Capsule::schema()->create('subscribers', function ($table) {
            $table->id();
            $table->string('email');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Capsule::schema()->create('subscriber_sub_group', function ($table) {
            $table->id();
            $table->foreignId('subscriber_id');
            $table->foreignId('subscriber_sub_group_id');
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
        });

        Capsule::schema()->create('campaign_audiences', function ($table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable();
            $table->string('targetable_type')->nullable();
            $table->unsignedBigInteger('targetable_id')->nullable();
            $table->boolean('send_to_all')->default(false);
            $table->timestamps();
        });

        $products = new ScopedCampaignProductSelector(
            new StatamicUserIdentityBridge,
            new ScopeResolver('active'),
        );
        $this->selector = new ScopedSubscriberGroupProductSelector($products);
        $this->deletions = new ScopedSubscriberGroupDeletionService($this->selector);
    }

    public function test_lists_only_products_in_direct_active_scope_with_active_organisations(): void
    {
        $user = $this->createUser('statamic-123');
        $allowed = $this->createProduct('allowed');
        $inactiveScope = $this->createProduct('inactive-scope');
        $inactiveOrganisation = $this->createProduct('inactive-organisation', 'active', 'inactive');
        $unscoped = $this->createProduct('unscoped');
        $this->scope($user, $allowed);
        $this->scope($user, $inactiveScope, 'inactive');
        $this->scope($user, $inactiveOrganisation);

        $products = $this->selector->productsFor($this->statamicUser('statamic-123'));

        $this->assertSame([$allowed->getKey()], $products->modelKeys());
        $this->assertNotContains($unscoped->getKey(), $products->modelKeys());
    }

    public function test_resolves_an_explicit_product_and_owned_group_inside_scope(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $group = $this->createGroup($product);
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $this->assertSame($product->getKey(), $this->selector->resolve($operator, $product->getKey())?->getKey());
        $this->assertSame($product->getKey(), $this->selector->resolveGroup($operator, $group)?->getKey());
    }

    public function test_group_resolution_fails_closed_for_unowned_or_conflicting_state(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $otherProduct = $this->createProduct('other');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $unowned = SubscriberGroup::query()->create([
            'name' => 'Unowned',
            'slug' => 'unowned',
            'collection_handle' => $product->primary_collection_handle,
        ]);
        $conflictingOrganisation = $this->createGroup($product, $otherProduct->organisation_id);
        $wrongCollection = $this->createGroup($product);
        $wrongCollection->update(['collection_handle' => $otherProduct->primary_collection_handle]);

        $this->assertNull($this->selector->resolveGroup($operator, $unowned));
        $this->assertNull($this->selector->resolveGroup($operator, $conflictingOrganisation));
        $this->assertNull($this->selector->resolveGroup($operator, $wrongCollection));
        $this->assertNull($this->selector->resolveGroup($operator, $this->createGroup($otherProduct)));
    }

    public function test_group_resolution_fails_when_product_or_organisation_is_inactive(): void
    {
        $user = $this->createUser('statamic-123');
        $inactiveProduct = $this->createProduct('inactive-product', 'inactive');
        $inactiveOrganisation = $this->createProduct('inactive-organisation', 'active', 'inactive');
        $this->scope($user, $inactiveProduct);
        $this->scope($user, $inactiveOrganisation);
        $operator = $this->statamicUser('statamic-123');

        $this->assertNull($this->selector->resolveGroup($operator, $this->createGroup($inactiveProduct)));
        $this->assertNull($this->selector->resolveGroup($operator, $this->createGroup($inactiveOrganisation)));
    }

    public function test_subgroup_resolution_inherits_scope_and_requires_the_exact_parent_group(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $group = $this->createGroup($product);
        $otherGroup = $this->createGroup($product);
        $subGroup = $this->createSubGroup($group);
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $this->assertSame(
            $product->getKey(),
            $this->selector->resolveSubGroup($operator, $group, $subGroup)?->getKey(),
        );
        $this->assertNull($this->selector->resolveSubGroup($operator, $otherGroup, $subGroup));
        $this->assertNull($this->selector->resolveSubGroup(
            $this->statamicUser('missing-link'),
            $group,
            $subGroup,
        ));
    }

    public function test_group_visibility_returns_only_exact_groups_in_direct_active_scope(): void
    {
        $user = $this->createUser('statamic-123');
        $allowed = $this->createProduct('allowed');
        $unscoped = $this->createProduct('unscoped');
        $inactiveProduct = $this->createProduct('inactive-product', 'inactive');
        $inactiveOrganisation = $this->createProduct('inactive-organisation', 'active', 'inactive');
        $this->scope($user, $allowed);
        $this->scope($user, $inactiveProduct);
        $this->scope($user, $inactiveOrganisation);

        $visible = $this->createGroup($allowed);
        $this->createGroup($unscoped);
        $this->createGroup($inactiveProduct);
        $this->createGroup($inactiveOrganisation);
        $this->createGroup($allowed, $unscoped->organisation_id);
        $wrongCollection = $this->createGroup($allowed);
        $wrongCollection->update(['collection_handle' => $unscoped->primary_collection_handle]);
        SubscriberGroup::query()->create([
            'name' => 'Unowned',
            'slug' => 'unowned',
            'collection_handle' => $allowed->primary_collection_handle,
        ]);

        $groups = $this->selector->groupsFor($this->statamicUser('statamic-123'));

        $this->assertSame([$visible->getKey()], $groups->modelKeys());
        $this->assertTrue($this->selector->groupsFor($this->statamicUser('missing-link'))->isEmpty());
    }

    public function test_guarded_delete_removes_only_an_exact_group_in_direct_active_scope(): void
    {
        $user = $this->createUser('statamic-123');
        $allowed = $this->createProduct('allowed');
        $unscoped = $this->createProduct('unscoped');
        $inactiveProduct = $this->createProduct('inactive-product', 'inactive');
        $inactiveOrganisation = $this->createProduct('inactive-organisation', 'active', 'inactive');
        $this->scope($user, $allowed);
        $this->scope($user, $inactiveProduct);
        $this->scope($user, $inactiveOrganisation);
        $operator = $this->statamicUser('statamic-123');

        $allowedGroup = $this->createGroup($allowed);
        $unscopedGroup = $this->createGroup($unscoped);
        $conflictingGroup = $this->createGroup($allowed, $unscoped->organisation_id);
        $wrongCollectionGroup = $this->createGroup($allowed);
        $wrongCollectionGroup->update(['collection_handle' => $unscoped->primary_collection_handle]);
        $inactiveProductGroup = $this->createGroup($inactiveProduct);
        $inactiveOrganisationGroup = $this->createGroup($inactiveOrganisation);
        $unownedGroup = SubscriberGroup::query()->create([
            'name' => 'Unowned',
            'slug' => 'unowned',
            'collection_handle' => $allowed->primary_collection_handle,
        ]);

        $this->assertFalse($this->deletions->delete($operator, $unscopedGroup));
        $this->assertFalse($this->deletions->delete($operator, $conflictingGroup));
        $this->assertFalse($this->deletions->delete($operator, $wrongCollectionGroup));
        $this->assertFalse($this->deletions->delete($operator, $inactiveProductGroup));
        $this->assertFalse($this->deletions->delete($operator, $inactiveOrganisationGroup));
        $this->assertFalse($this->deletions->delete($operator, $unownedGroup));
        $this->assertFalse($this->deletions->delete($this->statamicUser('missing-link'), $allowedGroup));
        $this->assertTrue($this->deletions->delete($operator, $allowedGroup));

        $this->assertFalse(SubscriberGroup::query()->whereKey($allowedGroup->getKey())->exists());
        $this->assertTrue(SubscriberGroup::query()->whereKey($unscopedGroup->getKey())->exists());
        $this->assertTrue(SubscriberGroup::query()->whereKey($conflictingGroup->getKey())->exists());
        $this->assertTrue(SubscriberGroup::query()->whereKey($wrongCollectionGroup->getKey())->exists());
        $this->assertTrue(SubscriberGroup::query()->whereKey($inactiveProductGroup->getKey())->exists());
        $this->assertTrue(SubscriberGroup::query()->whereKey($inactiveOrganisationGroup->getKey())->exists());
        $this->assertTrue(SubscriberGroup::query()->whereKey($unownedGroup->getKey())->exists());
    }

    public function test_delete_denies_group_or_subgroup_with_campaign_history(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $targetedGroup = $this->createGroup($product);
        $targetedSubGroup = $this->createSubGroup($this->createGroup($product));
        $this->targetAudience('subscriber_group', $targetedGroup->getKey());
        $this->targetAudience('subscriber_sub_group', $targetedSubGroup->getKey());

        $this->assertFalse($this->deletions->delete($operator, $targetedGroup));
        $this->assertFalse($this->deletions->deleteSubGroup($operator, $targetedSubGroup->group, $targetedSubGroup));
        $this->assertTrue(SubscriberGroup::query()->whereKey($targetedGroup->getKey())->exists());
        $this->assertTrue(SubscriberSubGroup::query()->whereKey($targetedSubGroup->getKey())->exists());
    }

    public function test_delete_requires_membership_removal_before_deleting_group_or_subgroup(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $group = $this->createGroup($product);
        $subGroup = $this->createSubGroup($group);
        $subscriber = $this->createSubscriber($subGroup);

        $this->assertFalse($this->deletions->delete($operator, $group));
        $this->assertFalse($this->deletions->deleteSubGroup($operator, $group, $subGroup));

        Capsule::table('subscriber_sub_group')
            ->where('subscriber_id', $subscriber->getKey())
            ->update(['unsubscribed_at' => '2026-07-31 00:00:00']);

        $this->assertTrue($this->deletions->deleteSubGroup($operator, $group, $subGroup));
        $this->assertTrue(Subscriber::query()->whereKey($subscriber->getKey())->exists());
    }

    public function test_group_delete_preserves_subscriber_identity_after_membership_is_removed(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $group = $this->createGroup($product);
        $subGroup = $this->createSubGroup($group);
        $subscriber = $this->createSubscriber($subGroup, unsubscribed: true);

        $this->assertTrue($this->deletions->delete($operator, $group));
        $this->assertFalse(SubscriberGroup::query()->whereKey($group->getKey())->exists());
        $this->assertTrue(Subscriber::query()->whereKey($subscriber->getKey())->exists());
    }

    public function test_archive_requires_scope_and_campaign_history(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $otherProduct = $this->createProduct('other');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $unused = $this->createGroup($product);
        $targeted = $this->createGroup($product);
        $targetedBySubGroup = $this->createGroup($product);
        $subGroup = $this->createSubGroup($targetedBySubGroup);
        $unscopedTargeted = $this->createGroup($otherProduct);
        $this->targetAudience('subscriber_group', $targeted->getKey());
        $this->targetAudience('subscriber_sub_group', $subGroup->getKey());
        $this->targetAudience('subscriber_group', $unscopedTargeted->getKey());

        $this->assertFalse($this->deletions->archive($operator, $unused));
        $this->assertFalse($this->deletions->archive($operator, $unscopedTargeted));
        $this->assertTrue($this->deletions->archive($operator, $targeted));
        $this->assertTrue($this->deletions->archive($operator, $targetedBySubGroup));
        $this->assertNotNull($targeted->refresh()->archived_at);
        $this->assertNotNull($targetedBySubGroup->refresh()->archived_at);
    }

    public function test_archive_subgroup_requires_scope_and_campaign_history(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $group = $this->createGroup($product);
        $unused = $this->createSubGroup($group);
        $targeted = $this->createSubGroup($group);
        $this->targetAudience('subscriber_sub_group', $targeted->getKey());

        $this->assertFalse($this->deletions->archiveSubGroup($operator, $group, $unused));
        $this->assertTrue($this->deletions->archiveSubGroup($operator, $group, $targeted));
        $this->assertNotNull($targeted->refresh()->archived_at);
        $this->assertTrue(Capsule::table('campaign_audiences')->where('targetable_id', $targeted->getKey())->exists());
    }

    public function test_restore_requires_archived_group_scope_and_preserves_child_archive_state(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $otherProduct = $this->createProduct('other');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $group = $this->createGroup($product);
        $activeGroup = $this->createGroup($product);
        $archivedChild = $this->createSubGroup($group);
        $subscriber = $this->createSubscriber($archivedChild);
        $unscoped = $this->createGroup($otherProduct);

        $group->forceFill([
            'archived_at' => '2026-08-18 00:00:00',
            'archived_by' => 44,
        ])->save();
        $archivedChild->forceFill([
            'archived_at' => '2026-08-18 00:00:00',
            'archived_by' => 45,
        ])->save();
        $unscoped->forceFill([
            'archived_at' => '2026-08-18 00:00:00',
            'archived_by' => 46,
        ])->save();

        $this->assertFalse($this->deletions->restore($operator, $activeGroup));
        $this->assertFalse($this->deletions->restore($operator, $unscoped));
        $this->assertFalse($this->deletions->restore($this->statamicUser('missing-link'), $group));
        $this->assertTrue($this->deletions->restore($operator, $group));

        $this->assertNull($group->refresh()->archived_at);
        $this->assertNull($group->archived_by);
        $this->assertNotNull($archivedChild->refresh()->archived_at);
        $this->assertTrue(Subscriber::query()->whereKey($subscriber->getKey())->exists());
        $this->assertTrue(Capsule::table('subscriber_sub_group')->where([
            'subscriber_id' => $subscriber->getKey(),
            'subscriber_sub_group_id' => $archivedChild->getKey(),
            'unsubscribed_at' => null,
        ])->exists());
    }

    public function test_restore_subgroup_requires_archived_state_exact_scope_and_an_active_parent_group(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $otherProduct = $this->createProduct('other');
        $this->scope($user, $product);
        $operator = $this->statamicUser('statamic-123');

        $group = $this->createGroup($product);
        $otherGroup = $this->createGroup($product);
        $subGroup = $this->createSubGroup($group);
        $activeSubGroup = $this->createSubGroup($group);
        $otherProductGroup = $this->createGroup($otherProduct);
        $otherProductSubGroup = $this->createSubGroup($otherProductGroup);
        $subscriber = $this->createSubscriber($subGroup);

        $subGroup->forceFill([
            'archived_at' => '2026-08-18 00:00:00',
            'archived_by' => 54,
        ])->save();
        $otherProductSubGroup->forceFill([
            'archived_at' => '2026-08-18 00:00:00',
            'archived_by' => 55,
        ])->save();

        $this->assertFalse($this->deletions->restoreSubGroup($operator, $group, $activeSubGroup));
        $this->assertFalse($this->deletions->restoreSubGroup($operator, $otherGroup, $subGroup));
        $this->assertFalse($this->deletions->restoreSubGroup($this->statamicUser('missing-link'), $group, $subGroup));
        $this->assertFalse($this->deletions->restoreSubGroup($operator, $otherProductGroup, $otherProductSubGroup));
        $this->assertTrue($this->deletions->restoreSubGroup($operator, $group, $subGroup));

        $this->assertNull($subGroup->refresh()->archived_at);
        $this->assertNull($subGroup->archived_by);
        $this->assertTrue(Subscriber::query()->whereKey($subscriber->getKey())->exists());
        $this->assertTrue(Capsule::table('subscriber_sub_group')->where([
            'subscriber_id' => $subscriber->getKey(),
            'subscriber_sub_group_id' => $subGroup->getKey(),
            'unsubscribed_at' => null,
        ])->exists());

        $group->forceFill([
            'archived_at' => '2026-08-18 00:00:00',
            'archived_by' => 56,
        ])->save();
        $subGroup->forceFill([
            'archived_at' => '2026-08-18 00:00:00',
            'archived_by' => 57,
        ])->save();

        $this->assertFalse($this->deletions->restoreSubGroup($operator, $group, $subGroup));
        $this->assertNotNull($group->refresh()->archived_at);
        $this->assertNotNull($subGroup->refresh()->archived_at);
    }

    public function test_archived_groups_remain_scoped_for_management_visibility(): void
    {
        $user = $this->createUser('statamic-123');
        $product = $this->createProduct('allowed');
        $this->scope($user, $product);
        $group = $this->createGroup($product);
        $group->forceFill(['archived_at' => '2026-07-31 00:00:00'])->save();

        $groups = $this->selector->groupsFor($this->statamicUser('statamic-123'));

        $this->assertSame([$group->getKey()], $groups->modelKeys());
        $this->assertSame($product->getKey(), $this->selector->resolveGroup($this->statamicUser('statamic-123'), $group)?->getKey());
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
        string $slug,
        string $status = 'active',
        string $organisationStatus = 'active',
    ): Product {
        $organisation = Organisation::query()->create([
            'name' => $slug.' organisation',
            'slug' => $slug.'-organisation',
            'status' => $organisationStatus,
        ]);

        return Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => $slug,
            'slug' => $slug,
            'status' => $status,
            'primary_collection_handle' => $slug.'_newsletters',
        ]);
    }

    private function createGroup(Product $product, ?int $organisationId = null): SubscriberGroup
    {
        return SubscriberGroup::query()->create([
            'organisation_id' => $organisationId ?? $product->organisation_id,
            'product_id' => $product->getKey(),
            'name' => 'Group '.$product->slug.' '.SubscriberGroup::query()->count(),
            'slug' => 'group-'.$product->slug.'-'.SubscriberGroup::query()->count(),
            'collection_handle' => $product->primary_collection_handle,
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

    private function createSubGroup(SubscriberGroup $group): SubscriberSubGroup
    {
        return SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $group->getKey(),
            'name' => 'Subgroup '.$group->getKey(),
            'slug' => 'subgroup-'.$group->getKey(),
        ]);
    }

    private function createSubscriber(SubscriberSubGroup $subGroup, bool $unsubscribed = false): Subscriber
    {
        $subscriber = Subscriber::query()->create([
            'email' => 'subscriber-'.Subscriber::query()->count().'@example.com',
            'status' => 'active',
        ]);

        Capsule::table('subscriber_sub_group')->insert([
            'subscriber_id' => $subscriber->getKey(),
            'subscriber_sub_group_id' => $subGroup->getKey(),
            'subscribed_at' => '2026-07-31 00:00:00',
            'unsubscribed_at' => $unsubscribed ? '2026-07-31 00:00:00' : null,
        ]);

        return $subscriber;
    }

    private function targetAudience(string $type, int $id): void
    {
        Capsule::table('campaign_audiences')->insert([
            'campaign_id' => 1,
            'targetable_type' => $type,
            'targetable_id' => $id,
            'send_to_all' => false,
            'created_at' => '2026-07-31 00:00:00',
            'updated_at' => '2026-07-31 00:00:00',
        ]);
    }

    /**
     * @return StatamicUser&Stub
     */
    private function statamicUser(string $id): StatamicUser
    {
        $user = $this->createStub(StatamicUser::class);
        $user->method('getAuthIdentifier')->willReturn($id);

        return $user;
    }
}
