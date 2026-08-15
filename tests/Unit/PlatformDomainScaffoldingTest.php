<?php

namespace Tests\Unit;

use App\Contracts\Domain\DomainResolverInterface;
use App\Contracts\Domain\RequestContextResolverInterface;
use App\Models\Organisation;
use App\Models\Product;
use App\Support\Platform\Domain\DomainResolver;
use App\Support\Platform\Domain\ProductUrlGenerator;
use App\Support\Platform\Domain\RequestContextResolver;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class PlatformDomainScaffoldingTest extends TestCase
{
    protected Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        Container::setInstance($container);
        Facade::setFacadeApplication($container);

        $container->instance('config', new Repository([
            'platform' => [
                'domain' => [
                    'platform_scheme' => 'https',
                    'platform_domain' => 'platform.example.test',
                    'default_surface_policy' => 'product_preferred',
                    'surface_policies' => [
                        'product_required',
                        'product_preferred',
                        'organisation_fallback',
                        'platform_only',
                    ],
                    'surfaces' => [
                        'landing_page' => [
                            'policy' => 'product_preferred',
                            'product_domain_field' => 'public_domain',
                            'path' => '/',
                        ],
                        'form_page' => [
                            'policy' => 'product_preferred',
                            'product_domain_field' => 'forms_domain',
                            'path' => '/subscribe/{form}',
                        ],
                        'form_submit_endpoint' => [
                            'policy' => 'product_preferred',
                            'product_domain_field' => 'forms_domain',
                            'path' => '/subscribe/{form}',
                        ],
                        'preferences_page' => [
                            'policy' => 'product_preferred',
                            'product_domain_field' => 'public_domain',
                            'path' => '/preferences/{subscriber}',
                        ],
                        'unsubscribe_page' => [
                            'policy' => 'product_preferred',
                            'product_domain_field' => 'public_domain',
                            'path' => '/unsubscribe/{subscriber}',
                        ],
                        'browser_view_page' => [
                            'policy' => 'product_preferred',
                            'product_domain_field' => 'public_domain',
                            'path' => '/browser-view/{campaign}',
                        ],
                        'campaign_link' => [
                            'policy' => 'product_preferred',
                            'product_domain_field' => 'public_domain',
                        ],
                        'cp' => [
                            'policy' => 'platform_only',
                        ],
                    ],
                ],
            ],
        ]));

        $this->capsule = new Capsule;
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        $schema = $this->capsule->schema();

        $schema->create('organisations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('default_domain')->nullable();
            $table->string('default_mail_domain')->nullable();
            $table->string('default_from_name')->nullable();
            $table->string('default_reply_to')->nullable();
            $table->text('compliance_profile')->nullable();
            $table->string('support_contact')->nullable();
            $table->timestamps();
        });

        $schema->create('products', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organisation_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active');
            $table->string('product_type');
            $table->string('public_domain')->nullable();
            $table->string('mail_from_domain')->nullable();
            $table->string('forms_domain')->nullable();
            $table->string('domain_status')->default('unconfigured');
            $table->timestamp('domain_verified_at')->nullable();
            $table->boolean('domain_is_primary')->default(false);
            $table->string('primary_collection_handle')->nullable();
            $table->text('default_sender_profile')->nullable();
            $table->string('default_template_family')->nullable();
            $table->boolean('fallback_to_platform_domain')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        $schema = $this->capsule->schema();

        $schema->dropIfExists('products');
        $schema->dropIfExists('organisations');

        Facade::clearResolvedInstances();
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_domain_resolver_prefers_verified_product_domain_then_organisation_then_platform(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte',
            'slug' => 'dataphyte',
            'default_domain' => 'org.example.test',
        ]);

        $product = Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Policy Point',
            'slug' => 'policy-point',
            'product_type' => 'newsletter',
            'public_domain' => 'policy.example.test',
            'forms_domain' => 'join.policy.example.test',
            'domain_status' => 'verified',
            'domain_verified_at' => '2026-07-30 12:00:00',
            'fallback_to_platform_domain' => true,
        ]);

        $resolver = new DomainResolver;

        $this->assertSame('policy.example.test', $resolver->resolveProductDomain('policy-point', 'landing_page'));
        $this->assertSame('join.policy.example.test', $resolver->resolveProductDomain('policy-point', 'form_page'));

        $product->forceFill([
            'domain_status' => 'pending_verification',
            'domain_verified_at' => null,
        ])->save();

        $this->assertSame('org.example.test', $resolver->resolveProductDomain('policy-point', 'landing_page'));

        $organisation->forceFill(['default_domain' => null])->save();

        $this->assertSame('platform.example.test', $resolver->resolveProductDomain('policy-point', 'landing_page'));
    }

    public function test_product_required_policy_returns_null_when_verified_product_domain_is_not_available(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte',
            'slug' => 'dataphyte',
            'default_domain' => 'org.example.test',
        ]);

        Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Insight',
            'slug' => 'insight',
            'product_type' => 'newsletter',
            'public_domain' => 'insight.example.test',
            'domain_status' => 'pending_verification',
            'domain_verified_at' => null,
            'fallback_to_platform_domain' => true,
        ]);

        $resolver = new DomainResolver;

        $this->assertNull($resolver->resolveProductDomain('insight', 'product_required'));
        $this->assertSame('platform.example.test', $resolver->resolveProductDomain('missing-product', 'product_preferred'));
    }

    public function test_request_context_resolver_identifies_product_and_organisation_hosts(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte',
            'slug' => 'dataphyte',
            'default_domain' => 'org.example.test',
        ]);

        Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Policy Point',
            'slug' => 'policy-point',
            'product_type' => 'newsletter',
            'public_domain' => 'policy.example.test',
            'forms_domain' => 'join.policy.example.test',
            'domain_status' => 'verified',
            'domain_verified_at' => '2026-07-30 12:00:00',
            'primary_collection_handle' => 'policy_point',
        ]);

        $resolver = new RequestContextResolver(new DomainResolver);

        $productContext = $resolver->resolve('join.policy.example.test', '/subscribe/policy');
        $organisationContext = $resolver->resolve('org.example.test', '/preferences/token');
        $platformContext = $resolver->resolve('platform.example.test', '/cp');

        $this->assertSame('product', $productContext['scope_type']);
        $this->assertSame('forms_domain', $productContext['matched_domain_field']);
        $this->assertSame('policy-point', $productContext['product_slug']);

        $this->assertSame('organisation', $organisationContext['scope_type']);
        $this->assertSame('dataphyte', $organisationContext['organisation_slug']);

        $this->assertSame('platform', $platformContext['scope_type']);
    }

    public function test_product_url_generator_builds_scaffolded_urls_without_rewriting_runtime_routes(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte',
            'slug' => 'dataphyte',
            'default_domain' => 'org.example.test',
        ]);

        Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Policy Point',
            'slug' => 'policy-point',
            'product_type' => 'newsletter',
            'public_domain' => 'policy.example.test',
            'forms_domain' => 'join.policy.example.test',
            'domain_status' => 'verified',
            'domain_verified_at' => '2026-07-30 12:00:00',
            'fallback_to_platform_domain' => true,
        ]);

        $resolver = new DomainResolver;
        $generator = new ProductUrlGenerator($resolver);

        $this->assertSame('https://policy.example.test/', $generator->landingPage('policy-point'));
        $this->assertSame('https://join.policy.example.test/subscribe/policy-form', $generator->formPage('policy-point', 'policy-form'));
        $this->assertSame('https://policy.example.test/preferences/subscriber-token', $generator->preferencesPage('policy-point', 'subscriber-token'));
        $this->assertSame('https://policy.example.test/browser-view/campaign-1', $generator->browserViewPage('policy-point', 'campaign-1'));
        $this->assertSame('https://policy.example.test/archive/story', $generator->campaignLink('policy-point', '/archive/story'));
        $this->assertSame('https://external.example.test/story', $generator->campaignLink('policy-point', 'https://external.example.test/story'));
    }
}
