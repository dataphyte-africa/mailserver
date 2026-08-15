<?php

namespace Tests\Unit;

use App\Models\Organisation;
use App\Models\OrganisationUserScope;
use App\Models\Product;
use App\Models\ProductUserScope;
use App\Models\User;
use App\Support\Platform\Authorization\PermissionSlugs;
use App\Support\Platform\Authorization\PlatformPermissionRegistry;
use App\Support\Platform\Authorization\ScopeResolver;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class PlatformAuthorizationScaffoldingTest extends TestCase
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

        Capsule::schema()->create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('organisations', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('default_domain')->nullable();
            $table->string('default_mail_domain')->nullable();
            $table->string('default_from_name')->nullable();
            $table->string('default_reply_to')->nullable();
            $table->text('support_contact')->nullable();
            $table->text('compliance_profile')->nullable();
            $table->timestamps();
        });

        Capsule::schema()->create('products', function ($table) {
            $table->id();
            $table->foreignId('organisation_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('product_type')->default('newsletter');
            $table->string('public_domain')->nullable();
            $table->string('mail_from_domain')->nullable();
            $table->string('forms_domain')->nullable();
            $table->string('domain_status')->nullable();
            $table->timestamp('domain_verified_at')->nullable();
            $table->boolean('domain_is_primary')->default(false);
            $table->string('primary_collection_handle')->nullable();
            $table->text('default_sender_profile')->nullable();
            $table->string('default_template_family')->nullable();
            $table->boolean('fallback_to_platform_domain')->default(true);
            $table->timestamps();
        });

        Capsule::schema()->create('organisation_user_scope', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('organisation_id');
            $table->string('scope_role');
            $table->string('status')->default('active');
            $table->foreignId('granted_by')->nullable();
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
    }

    public function test_permission_registry_exposes_documented_slugs_by_category(): void
    {
        $registry = new PlatformPermissionRegistry(PermissionSlugs::categories());

        $this->assertTrue($registry->has(PermissionSlugs::NEWSLETTER_SEND));
        $this->assertSame('newsletter', $registry->categoryFor(PermissionSlugs::NEWSLETTER_SEND));
        $this->assertContains(PermissionSlugs::ANALYTICS_VIEW, $registry->slugsForCategory('analytics'));
    }

    public function test_scope_resolver_returns_active_organisation_scope_information(): void
    {
        $user = User::create(['name' => 'Ada', 'email' => 'ada@example.com']);
        $organisation = Organisation::create(['name' => 'Org', 'slug' => 'org']);

        OrganisationUserScope::create([
            'user_id' => $user->id,
            'organisation_id' => $organisation->id,
            'scope_role' => 'organisation_admin',
            'status' => 'active',
        ]);

        $resolver = new ScopeResolver('active');

        $this->assertTrue($resolver->hasOrganisationScope($user, $organisation->id));
        $this->assertSame([$organisation->id], $resolver->organisationIds($user));
        $this->assertSame('organisation_admin', $resolver->organisationScope($user, $organisation->id)?->scope_role);
    }

    public function test_scope_resolver_filters_inactive_or_wrong_role_scopes(): void
    {
        $user = User::create(['name' => 'Ada', 'email' => 'ada2@example.com']);
        $organisation = Organisation::create(['name' => 'Org 2', 'slug' => 'org-2']);

        OrganisationUserScope::create([
            'user_id' => $user->id,
            'organisation_id' => $organisation->id,
            'scope_role' => 'viewer',
            'status' => 'inactive',
        ]);

        $resolver = new ScopeResolver('active');

        $this->assertFalse($resolver->hasOrganisationScope($user, $organisation->id));
        $this->assertFalse($resolver->hasOrganisationScope($user, $organisation->id, ['organisation_admin']));
    }

    public function test_scope_resolver_returns_active_product_scope_information(): void
    {
        $user = User::create(['name' => 'Bola', 'email' => 'bola@example.com']);
        $organisation = Organisation::create(['name' => 'Org 3', 'slug' => 'org-3']);
        $product = Product::create([
            'organisation_id' => $organisation->id,
            'name' => 'Product',
            'slug' => 'product',
        ]);

        ProductUserScope::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'scope_role' => 'product_manager',
            'status' => 'active',
        ]);

        $resolver = new ScopeResolver('active');

        $this->assertTrue($resolver->hasProductScope($user, $product->id));
        $this->assertTrue($resolver->hasProductScope($user, $product->id, ['product_manager']));
        $this->assertSame([$product->id], $resolver->productIds($user));
        $this->assertSame('product_manager', $resolver->productScope($user, $product->id)?->scope_role);
    }
}
