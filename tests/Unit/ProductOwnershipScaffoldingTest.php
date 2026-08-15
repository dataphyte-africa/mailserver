<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\EmailTemplate;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

class ProductOwnershipScaffoldingTest extends TestCase
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

    public function test_product_owned_models_expose_additive_nullable_ownership_fields(): void
    {
        $subscriberGroup = new SubscriberGroup;
        $campaign = new Campaign;
        $template = new EmailTemplate;

        $this->assertContains('organisation_id', $subscriberGroup->getFillable());
        $this->assertContains('product_id', $subscriberGroup->getFillable());
        $this->assertContains('organisation_id', $campaign->getFillable());
        $this->assertContains('product_id', $campaign->getFillable());
        $this->assertContains('organisation_id', $template->getFillable());
        $this->assertContains('product_id', $template->getFillable());
    }

    public function test_product_owned_models_point_to_foundation_ownership_records(): void
    {
        $subscriberGroup = new SubscriberGroup;
        $campaign = new Campaign;
        $template = new EmailTemplate;

        $this->assertInstanceOf(BelongsTo::class, $subscriberGroup->organisation());
        $this->assertInstanceOf(BelongsTo::class, $subscriberGroup->product());
        $this->assertInstanceOf(BelongsTo::class, $campaign->organisation());
        $this->assertInstanceOf(BelongsTo::class, $campaign->product());
        $this->assertInstanceOf(BelongsTo::class, $template->organisation());
        $this->assertInstanceOf(BelongsTo::class, $template->product());
        $this->assertSame(Organisation::class, $subscriberGroup->organisation()->getRelated()::class);
        $this->assertSame(Product::class, $subscriberGroup->product()->getRelated()::class);
    }

    public function test_foundation_models_expose_owned_record_relationships(): void
    {
        $organisation = new Organisation;
        $product = new Product;

        $this->assertInstanceOf(HasMany::class, $organisation->subscriberGroups());
        $this->assertInstanceOf(HasMany::class, $organisation->campaigns());
        $this->assertInstanceOf(HasMany::class, $organisation->emailTemplates());
        $this->assertInstanceOf(HasMany::class, $product->subscriberGroups());
        $this->assertInstanceOf(HasMany::class, $product->campaigns());
        $this->assertInstanceOf(HasMany::class, $product->emailTemplates());
    }

    public function test_sub_groups_continue_to_inherit_ownership_from_parent_group(): void
    {
        $subGroup = new SubscriberSubGroup;

        $this->assertInstanceOf(BelongsTo::class, $subGroup->group());
        $this->assertSame(SubscriberGroup::class, $subGroup->group()->getRelated()::class);
    }
}
