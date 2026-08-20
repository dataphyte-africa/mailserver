<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductFormSubmission;
use App\Models\ProductUserScope;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Services\Forms\ProductFormService;
use App\Services\Platform\StatamicNewsletterProductSyncService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class ProductFormPlatformTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://mailserver.test',
            'platform.domain.platform_scheme' => 'https',
            'platform.domain.platform_domain' => 'mailserver.test',
        ]);
    }

    public function test_service_creates_product_owned_form_and_rejects_archived_audience_assignments(): void
    {
        $product = $this->createProduct();
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'archived_at' => null,
        ]);
        $subGroup = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $group->getKey(),
            'archived_at' => null,
        ]);

        /** @var ProductFormService $service */
        $service = app(ProductFormService::class);
        $form = $service->create($product, [
            'name' => 'Community Intake',
            'slug' => 'community-intake',
            'template_family' => 'application_basic',
            'headline' => 'Community Intake Form',
            'description' => 'Collect programme applications.',
            'success_message' => 'Thanks for applying.',
            'field_definitions' => $this->fieldDefinitions(),
            'allowed_origins' => ['https://widgets.example.test'],
            'audience_sub_group_id' => $subGroup->getKey(),
        ]);

        $this->assertSame($product->organisation_id, $form->organisation_id);
        $this->assertSame($product->getKey(), $form->product_id);
        $this->assertSame('application', $form->mode);
        $this->assertSame('application_basic', $form->template_family);
        $this->assertSame($group->getKey(), $form->audience_group_id);
        $this->assertSame($subGroup->getKey(), $form->audience_sub_group_id);

        $archivedGroup = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'archived_at' => now(),
        ]);

        try {
            $service->create($product, [
                'name' => 'Archived Assignment',
                'slug' => 'archived-assignment',
                'template_family' => 'application_basic',
                'field_definitions' => $this->fieldDefinitions(),
                'audience_group_id' => $archivedGroup->getKey(),
            ]);

            $this->fail('Expected archived audience assignment to fail closed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('audience_group_id', $exception->errors());
        }
    }

    public function test_hosted_form_urls_use_verified_product_domain_and_platform_fallback(): void
    {
        /** @var ProductFormService $service */
        $service = app(ProductFormService::class);

        $verifiedProduct = $this->createProduct([
            'slug' => 'policy-point',
            'name' => 'Policy Point',
            'forms_domain' => 'join.policy.example.test',
            'domain_status' => 'verified',
            'domain_verified_at' => now(),
        ]);
        $verifiedForm = $service->create($verifiedProduct, [
            'name' => 'Policy Point Intake',
            'slug' => 'policy-point-intake',
            'template_family' => 'application_basic',
            'field_definitions' => $this->fieldDefinitions(),
        ]);

        $verifiedResponse = $this->get(route('product-forms.public.show', ['form' => $verifiedForm->slug]));

        $verifiedResponse->assertOk()
            ->assertViewHas('hostedPageUrl', 'https://join.policy.example.test/forms/policy-point-intake')
            ->assertViewHas('submitUrl', 'https://join.policy.example.test/forms/policy-point-intake');

        $fallbackProduct = $this->createProduct([
            'slug' => 'field-monitor',
            'name' => 'Field Monitor',
            'forms_domain' => 'join.field.example.test',
            'domain_status' => 'pending_verification',
            'domain_verified_at' => null,
            'fallback_to_platform_domain' => true,
        ], [
            'slug' => 'dataphyte-no-domain',
            'default_domain' => null,
        ]);
        $fallbackForm = $service->create($fallbackProduct, [
            'name' => 'Field Monitor Intake',
            'slug' => 'field-monitor-intake',
            'template_family' => 'data_collection_basic',
            'field_definitions' => $this->fieldDefinitions(),
        ]);

        $fallbackResponse = $this->get(route('product-forms.public.show', ['form' => $fallbackForm->slug]));

        $fallbackResponse->assertOk()
            ->assertViewHas('hostedPageUrl', 'https://mailserver.test/forms/field-monitor-intake')
            ->assertViewHas('submitUrl', 'https://mailserver.test/forms/field-monitor-intake');
    }

    public function test_allowed_origin_submission_is_stored_and_disallowed_origin_is_rejected(): void
    {
        $product = $this->createProduct();

        /** @var ProductFormService $service */
        $service = app(ProductFormService::class);
        $form = $service->create($product, [
            'name' => 'Community Survey',
            'slug' => 'community-survey',
            'template_family' => 'data_collection_basic',
            'success_message' => 'Stored successfully.',
            'field_definitions' => $this->fieldDefinitions(),
            'allowed_origins' => ['https://widgets.example.test'],
        ]);

        $allowed = $this->withHeader('Origin', 'https://widgets.example.test')
            ->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
                'full_name' => 'Jane Doe',
                'email' => 'jane@example.test',
                'location' => 'lagos',
            ]);

        $allowed->assertCreated()
            ->assertJson([
                'status' => 'submitted',
                'success_message' => 'Stored successfully.',
            ]);

        $submission = ProductFormSubmission::query()->sole();
        $this->assertSame('Jane Doe', $submission->payload['full_name']);
        $this->assertSame('jane@example.test', $submission->payload['email']);
        $this->assertSame('https://widgets.example.test', $submission->submission_origin);

        $this->withHeader('Origin', 'https://disallowed.example.test')
            ->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
                'full_name' => 'Bad Origin',
                'email' => 'bad@example.test',
                'location' => 'lagos',
            ])
            ->assertForbidden();

        $this->assertSame(1, ProductFormSubmission::query()->count());
    }

    public function test_public_schema_endpoint_exposes_embed_contract(): void
    {
        $product = $this->createProduct([
            'forms_domain' => 'join.policy.example.test',
            'domain_status' => 'verified',
            'domain_verified_at' => now(),
        ]);

        /** @var ProductFormService $service */
        $service = app(ProductFormService::class);
        $form = $service->create($product, [
            'name' => 'Community Survey',
            'slug' => 'community-survey',
            'template_family' => 'data_collection_basic',
            'success_message' => 'Stored successfully.',
            'field_definitions' => $this->fieldDefinitions(),
            'allowed_origins' => ['https://widgets.example.test'],
        ]);

        $this->getJson(route('product-forms.public.schema', ['form' => $form->slug]))
            ->assertOk()
            ->assertJsonPath('slug', 'community-survey')
            ->assertJsonPath('mode', 'data_collection')
            ->assertJsonPath('form_scope', 'product')
            ->assertJsonPath('submit_url', 'https://join.policy.example.test/forms/community-survey')
            ->assertJsonPath('fields.0.handle', 'full_name')
            ->assertJsonPath('allowed_origins.1', 'https://widgets.example.test');
    }

    public function test_application_submissions_start_pending_review_and_can_be_reviewed_in_cp(): void
    {
        $this->withoutMiddleware(CountUsers::class);

        $product = $this->createProduct();

        /** @var ProductFormService $service */
        $service = app(ProductFormService::class);
        $form = $service->create($product, [
            'name' => 'Application Review',
            'slug' => 'application-review',
            'template_family' => 'application_basic',
            'field_definitions' => $this->fieldDefinitions(),
        ]);

        $this->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
            'full_name' => 'Review Candidate',
            'email' => 'review@example.test',
            'location' => 'lagos',
        ])->assertCreated();

        $submission = ProductFormSubmission::query()->sole();
        $this->assertSame('pending_review', $submission->status);

        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->scopeStatamicUserToProduct($user, $product);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->post(cp_route('product-forms.submissions.status', [$form, $submission]), [
                'status' => 'approved',
            ])
            ->assertRedirect();

        $submission->refresh();

        $this->assertSame('approved', $submission->status);
        $this->assertNotEmpty($submission->metadata['reviewed_at'] ?? null);
    }

    public function test_cp_listing_and_export_expose_stored_submissions(): void
    {
        $product = $this->createProduct();

        /** @var ProductFormService $service */
        $service = app(ProductFormService::class);
        $form = $service->create($product, [
            'name' => 'Operator Intake',
            'slug' => 'operator-intake',
            'template_family' => 'application_basic',
            'field_definitions' => $this->fieldDefinitions(),
            'allowed_origins' => ['https://widgets.example.test'],
        ]);

        $this->withHeader('Origin', 'https://widgets.example.test')
            ->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
                'full_name' => 'John Example',
                'email' => 'john@example.test',
                'location' => 'abuja',
            ])
            ->assertCreated();

        $this->withoutMiddleware(CountUsers::class);

        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->scopeStatamicUserToProduct($user, $product);

        $cpBase = '/'.trim((string) config('statamic.cp.route', 'cp'), '/').'/product-forms';

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get($cpBase)
            ->assertOk()
            ->assertSee('Operator Intake')
            ->assertSee('1 submissions');

        $submissions = $service->submissions($form);

        $this->assertCount(1, $submissions->items());
        $this->assertSame('John Example', $submissions->items()[0]->payload['full_name']);
        $this->assertSame('john@example.test', $submissions->items()[0]->payload['email']);

        $csv = $service->csvContent($form);

        $this->assertStringContainsString('submission_id,status,submitted_at,submission_origin,full_name,email,location', $csv);
        $this->assertStringContainsString('john@example.test', $csv);
    }

    public function test_cp_create_and_edit_manage_product_owned_forms(): void
    {
        $this->withoutMiddleware(CountUsers::class);

        $product = $this->createProduct();
        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->scopeStatamicUserToProduct($user, $product);

        $fieldDefinitions = json_encode($this->fieldDefinitions(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $this->assertIsString($fieldDefinitions);
        $cpBase = '/'.trim((string) config('statamic.cp.route', 'cp'), '/').'/product-forms';

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('product-forms.create'))
            ->assertOk()
            ->assertSee('Create Hosted Form')
            ->assertSee('Policy Point');

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->post(cp_route('product-forms.store'), [
                'form_scope' => 'product',
                'product_id' => $product->getKey(),
                'name' => 'Operator Intake',
                'slug' => 'operator-intake',
                'template_family' => 'application_basic',
                'status' => 'published',
                'headline' => 'Operator Intake',
                'description' => 'Collect operator details.',
                'success_message' => 'Thanks.',
                'field_definitions_json' => $fieldDefinitions,
                'allowed_origins_text' => "https://widgets.example.test\nhttps://partner.example.test",
                'requires_review' => '1',
            ])
            ->assertRedirect($cpBase);

        $form = \App\Models\ProductForm::query()->where('slug', 'operator-intake')->firstOrFail();

        $this->assertSame($product->getKey(), $form->product_id);
        $this->assertSame($product->organisation_id, $form->organisation_id);
        $this->assertSame(['https://widgets.example.test', 'https://partner.example.test'], $form->allowed_origins);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('product-forms.edit', $form))
            ->assertOk()
            ->assertSee('Edit Hosted Form')
            ->assertSee('operator-intake');

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->put(cp_route('product-forms.update', $form), [
                'form_scope' => 'product',
                'product_id' => $product->getKey(),
                'name' => 'Updated Operator Intake',
                'slug' => 'updated-operator-intake',
                'template_family' => 'data_collection_basic',
                'status' => 'draft',
                'headline' => 'Updated Operator Intake',
                'description' => 'Updated details.',
                'success_message' => 'Stored.',
                'field_definitions_json' => $fieldDefinitions,
                'allowed_origins_text' => 'https://widgets.example.test',
            ])
            ->assertRedirect($cpBase);

        $form->refresh();

        $this->assertSame('Updated Operator Intake', $form->name);
        $this->assertSame('updated-operator-intake', $form->slug);
        $this->assertSame('data_collection', $form->mode);
        $this->assertSame('draft', $form->status);
        $this->assertFalse($form->requires_review);
    }

    public function test_cp_create_shows_setup_message_when_no_product_is_available(): void
    {
        $this->withoutMiddleware(CountUsers::class);

        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('product-forms.create'))
            ->assertOk()
            ->assertSee('No active product is available')
            ->assertSee('A hosted form must belong to an active product');
    }

    public function test_sync_creates_organisations_from_collections_and_products_from_blueprints(): void
    {
        /** @var StatamicNewsletterProductSyncService $sync */
        $sync = app(StatamicNewsletterProductSyncService::class);

        $result = $sync->sync();

        $this->assertNotEmpty($result['organisations']);
        $this->assertNotEmpty($result['products']);

        $insight = Organisation::query()->where('primary_collection_handle', 'insight_newsletters')->firstOrFail();
        $this->assertSame('Dataphyte Insight', $insight->name);

        $dataDive = Product::query()
            ->where('primary_collection_handle', 'insight_newsletters')
            ->where('blueprint_handle', 'data_dive')
            ->firstOrFail();

        $this->assertSame($insight->getKey(), $dataDive->organisation_id);
        $this->assertSame('Data Dive', $dataDive->name);
    }

    public function test_organisation_scoped_form_routes_submission_to_selected_same_organisation_product(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Insight',
            'slug' => 'insight-newsletters',
            'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $dataDive = Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Data Dive',
            'slug' => 'insight-newsletters-data-dive',
            'status' => 'active',
            'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters',
            'blueprint_handle' => 'data_dive',
            'domain_status' => 'unconfigured',
        ]);
        $marina = Product::query()->create([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Marina and Maitama',
            'slug' => 'insight-newsletters-marina-maitama',
            'status' => 'active',
            'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters',
            'blueprint_handle' => 'marina_maitama',
            'domain_status' => 'unconfigured',
        ]);
        $otherProduct = $this->createProduct([
            'name' => 'Foundation Weekly',
            'slug' => 'foundation-newsletters-weekly',
            'primary_collection_handle' => 'foundation_newsletters',
            'blueprint_handle' => 'weekly',
        ]);

        /** @var ProductFormService $service */
        $service = app(ProductFormService::class);
        $form = $service->createForOrganisation($organisation, [
            'name' => 'Insight Product Choice',
            'slug' => 'insight-product-choice',
            'template_family' => 'data_collection_basic',
            'field_definitions' => $this->fieldDefinitionsWithProductChoice($dataDive, $marina),
            'product_selection_field' => 'product_choice',
            'allowed_product_ids' => [$dataDive->getKey(), $marina->getKey()],
        ]);

        $this->assertSame('organisation', $form->form_scope);
        $this->assertNull($form->product_id);
        $this->assertSame('product_choice', $form->product_selection_field);

        $this->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
            'full_name' => 'Ada Example',
            'email' => 'ada@example.test',
            'product_choice' => (string) $marina->getKey(),
        ])->assertCreated();

        $submission = ProductFormSubmission::query()->sole();
        $this->assertSame($organisation->getKey(), $submission->organisation_id);
        $this->assertSame($marina->getKey(), $submission->product_id);

        $this->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
            'full_name' => 'Blocked Example',
            'email' => 'blocked@example.test',
            'product_choice' => (string) $otherProduct->getKey(),
        ])->assertUnprocessable();

        $this->assertSame(1, ProductFormSubmission::query()->count());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fieldDefinitions(): array
    {
        return [
            [
                'handle' => 'full_name',
                'label' => 'Full Name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'handle' => 'email',
                'label' => 'Email Address',
                'type' => 'email',
                'required' => true,
            ],
            [
                'handle' => 'location',
                'label' => 'Location',
                'type' => 'select',
                'required' => true,
                'options' => [
                    ['label' => 'Abuja', 'value' => 'abuja'],
                    ['label' => 'Lagos', 'value' => 'lagos'],
                ],
            ],
        ];
    }

    private function fieldDefinitionsWithProductChoice(Product ...$products): array
    {
        return [
            [
                'handle' => 'full_name',
                'label' => 'Full Name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'handle' => 'email',
                'label' => 'Email Address',
                'type' => 'email',
                'required' => true,
            ],
            [
                'handle' => 'product_choice',
                'label' => 'Select Product',
                'type' => 'select',
                'required' => true,
                'options' => collect($products)
                    ->map(fn (Product $product): array => [
                        'label' => $product->name,
                        'value' => (string) $product->getKey(),
                    ])
                    ->all(),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $productOverrides
     * @param  array<string, mixed>  $organisationOverrides
     */
    private function createProduct(array $productOverrides = [], array $organisationOverrides = []): Product
    {
        $organisation = Organisation::query()->create(array_merge([
            'name' => 'Dataphyte',
            'slug' => 'dataphyte-'.Str::slug((string) ($productOverrides['slug'] ?? 'default')),
            'status' => 'active',
            'default_domain' => 'org.example.test',
        ], $organisationOverrides));

        return Product::query()->create(array_merge([
            'organisation_id' => $organisation->getKey(),
            'name' => 'Policy Point',
            'slug' => 'policy-point',
            'status' => 'active',
            'product_type' => 'forms',
            'forms_domain' => 'join.policy.example.test',
            'domain_status' => 'verified',
            'domain_verified_at' => now(),
            'fallback_to_platform_domain' => true,
        ], $productOverrides));
    }

    private function scopeStatamicUserToProduct(\Statamic\Contracts\Auth\User $statamicUser, Product $product): void
    {
        $user = app(StatamicUserIdentityBridgeInterface::class)->provision($statamicUser);

        ProductUserScope::query()->updateOrCreate([
            'user_id' => $user->getKey(),
            'product_id' => $product->getKey(),
        ], [
            'scope_role' => 'product_manager',
            'status' => 'active',
            'granted_by' => $user->getKey(),
        ]);
    }
}
