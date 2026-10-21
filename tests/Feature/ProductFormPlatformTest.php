<?php

namespace Tests\Feature;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Mail\SubscriptionConfirmationMail;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\ProductFormSubmission;
use App\Models\ProductUserScope;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Forms\ProductFormService;
use App\Services\Newsletter\NewsletterPublicUrlService;
use App\Services\Newsletter\SubscriptionFormService;
use App\Services\Platform\OrganisationNewsletterDomainService;
use App\Services\Platform\StatamicNewsletterProductSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Statamic\Contracts\Auth\User;
use Statamic\Facades\Form as StatamicForm;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class ProductFormPlatformTest extends TestCase
{
    public function test_subscription_profile_fields_use_shared_countries_and_custom_welcome_copy(): void
    {
        Mail::fake();
        $product = $this->createProduct([], ['banner_path' => 'organisations/banner.jpg']);
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'collection_handle' => $product->primary_collection_handle,
            'is_public_preference' => true,
        ]);
        $subGroup = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->getKey()]);
        $form = app(ProductFormService::class)->create($product, [
            'name' => 'Insight Signup', 'slug' => 'insight-signup-fields',
            'template_family' => 'subscription_basic',
            'field_definitions' => [
                ['handle' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ['handle' => 'country', 'label' => 'Country', 'type' => 'country', 'required' => false],
                ['handle' => 'profession', 'label' => 'Profession', 'type' => 'text', 'required' => false],
                ['handle' => 'gender', 'label' => 'Gender', 'type' => 'select', 'required' => false, 'options' => [
                    ['label' => 'Male', 'value' => 'male'], ['label' => 'Female', 'value' => 'female'], ['label' => 'Other', 'value' => 'other'],
                ]],
            ],
            'settings' => [
                'welcome_subject' => 'Welcome to Insight', 'welcome_body' => 'Your updates are on the way.',
                'resubscribe_subject' => 'Welcome back to Insight', 'resubscribe_body' => 'Good to see you again.',
                'send_update_email' => true, 'update_subject' => 'Insight choices updated', 'update_body' => 'Your choices were saved.',
                'show_banner' => true,
            ],
        ]);

        $this->assertGreaterThan(200, DB::table('countries')->count());
        $schema = $this->getJson(route('product-forms.public.schema', ['form' => $form->slug]))->assertOk()->json();
        $this->assertContains(['value' => 'NGA', 'label' => 'Nigeria'], collect($schema['fields'])->firstWhere('handle', 'country')['options']);
        $this->assertStringContainsString('banner.jpg', $schema['banner_url']);
        $this->get(route('product-forms.public.show', ['form' => $form->slug]))
            ->assertOk()->assertSee('banner.jpg')
            ->assertSee('data-country-picker', false)
            ->assertSee('data-country-search', false);

        $endpoint = route('product-forms.public.submit', ['form' => $form->slug]);
        $this->postJson($endpoint, ['email' => 'person@example.test', 'country' => 'XXX', 'preferences' => [$subGroup->getKey()]])
            ->assertUnprocessable()->assertJsonValidationErrors('country');
        $this->postJson($endpoint, [
            'email' => 'person@example.test', 'country' => 'NGA', 'profession' => 'Reporter', 'gender' => 'female',
            'preferences' => [$subGroup->getKey()],
        ])->assertCreated()->assertJsonPath('status', 'active');

        $this->assertSame('NGA', ProductFormSubmission::query()->firstOrFail()->payload['country']);
        Mail::assertQueued(SubscriptionConfirmationMail::class, fn (SubscriptionConfirmationMail $mail): bool => $mail->mailConfig['subject'] === 'Welcome to Insight'
            && $mail->mailConfig['body'] === 'Your updates are on the way.');

        $secondSubGroup = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->getKey()]);
        $subscriber = Subscriber::query()->where('email', 'person@example.test')->firstOrFail();

        $this->postJson($endpoint, [
            'email' => 'person@example.test', 'preferences' => [$secondSubGroup->getKey()],
        ])->assertCreated()->assertJsonPath('subscription_status', 'subscription_updated');
        Mail::assertQueued(SubscriptionConfirmationMail::class, fn (SubscriptionConfirmationMail $mail): bool => $mail->status === 'subscription_updated'
            && $mail->mailConfig['subject'] === 'Insight choices updated'
            && $mail->mailConfig['body'] === 'Your choices were saved.');
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://mailserver.test',
            'platform.domain.platform_scheme' => 'https',
            'platform.domain.platform_domain' => 'mailserver.test',
        ]);
    }

    public function test_product_subscription_form_activates_immediately_and_exposes_schema(): void
    {
        Mail::fake();
        $product = $this->createProduct();
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'collection_handle' => $product->primary_collection_handle,
            'archived_at' => null,
        ]);
        $subGroup = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $group->getKey(), 'archived_at' => null,
        ]);
        $form = app(ProductFormService::class)->create($product, [
            'name' => 'Product Signup', 'slug' => 'product-signup',
            'template_family' => 'subscription_basic',
            'field_definitions' => $this->fieldDefinitions(),
            'audience_group_id' => $group->getKey(),
            'audience_sub_group_id' => $subGroup->getKey(),
            'requires_review' => true,
        ]);
        $this->assertFalse($form->requires_review);

        $this->getJson(route('product-forms.public.schema', ['form' => $form->slug]))
            ->assertOk()
            ->assertJsonPath('mode', 'subscription')
            ->assertJsonPath('handle', 'product-signup')
            ->assertJsonPath('group.id', $group->getKey())
            ->assertJsonPath('endpoint', app(ProductFormService::class)->submitUrl($form))
            ->assertJsonPath('audience_sub_group_id', $subGroup->getKey());

        $endpoint = route('product-forms.public.submit', ['form' => $form->slug]);
        $this->postJson($endpoint, [
            'full_name' => 'Signup Person', 'email' => 'signup@example.test', 'location' => 'lagos',
            'preferences_present' => '1',
        ])->assertUnprocessable()->assertJsonValidationErrors('preferences');
        $this->postJson($endpoint, ['email' => 'signup@example.test', 'location' => 'lagos'])
            ->assertUnprocessable();

        $this->postJson($endpoint, [
            'full_name' => 'Signup Person', 'email' => 'signup@example.test', 'location' => 'lagos',
        ])->assertCreated()->assertJsonPath('status', 'active')->assertJsonPath('subscription_status', 'subscribed');

        $subscriber = Subscriber::query()->where('email', 'signup@example.test')->firstOrFail();
        $this->assertSame('active', $subscriber->status);
        $this->assertNull($subscriber->confirmed_at);
        $this->assertNull($subscriber->pending_confirmation_expires_at);
        $this->assertTrue($subscriber->subGroups()->whereKey($subGroup->getKey())->exists());
        Mail::assertQueued(SubscriptionConfirmationMail::class, fn ($mail) => $mail->hasTo('signup@example.test'));

        $this->postJson($endpoint, [
            'full_name' => 'Signup Person', 'email' => 'signup@example.test', 'location' => 'lagos',
        ])->assertCreated()->assertJsonPath('subscription_status', 'already_subscribed');
        Mail::assertQueued(SubscriptionConfirmationMail::class, 1);

        $this->assertTrue($subscriber->subGroups()->where('slug', 'rating-new')->exists());
    }

    public function test_hosted_subscription_rejects_suppressed_address_without_storing_a_submission(): void
    {
        Mail::fake();
        $product = $this->createProduct();
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'collection_handle' => $product->primary_collection_handle,
        ]);
        $subGroup = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->getKey()]);
        $form = app(ProductFormService::class)->create($product, [
            'name' => 'Protected Signup', 'slug' => 'protected-signup',
            'template_family' => 'subscription_basic',
            'field_definitions' => $this->fieldDefinitions(),
            'audience_group_id' => $group->getKey(),
            'audience_sub_group_id' => $subGroup->getKey(),
        ]);
        Subscriber::factory()->create(['email' => 'blocked@example.test', 'status' => 'bounced']);

        $this->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
            'full_name' => 'Blocked Person', 'email' => 'blocked@example.test', 'location' => 'lagos',
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('product_form_submissions', ['product_form_id' => $form->getKey()]);
        Mail::assertNothingQueued();
    }

    public function test_subscription_form_lists_only_approved_product_preferences_and_enrolls_selected_groups(): void
    {
        Mail::fake();
        $product = $this->createProduct();
        $otherProduct = $this->createProduct(['slug' => 'other-product']);
        $approved = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'collection_handle' => $product->primary_collection_handle,
            'is_public_preference' => true,
        ]);
        $second = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'collection_handle' => $product->primary_collection_handle,
            'is_public_preference' => true,
        ]);
        $hidden = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'collection_handle' => $product->primary_collection_handle,
            'is_public_preference' => false,
        ]);
        $foreign = SubscriberGroup::factory()->create([
            'organisation_id' => $otherProduct->organisation_id,
            'product_id' => $otherProduct->getKey(),
            'collection_handle' => $otherProduct->primary_collection_handle,
            'is_public_preference' => true,
        ]);
        $choices = collect([$approved, $second, $hidden, $foreign])->mapWithKeys(
            fn (SubscriberGroup $group) => [$group->getKey() => SubscriberSubGroup::factory()->create([
                'subscriber_group_id' => $group->getKey(),
            ])]
        );
        $form = app(ProductFormService::class)->create($product, [
            'name' => 'Public Signup', 'slug' => 'public-signup',
            'template_family' => 'subscription_basic',
            'field_definitions' => $this->fieldDefinitions(),
        ]);
        $schema = $this->getJson(route('product-forms.public.schema', ['form' => $form->slug]));
        $schema->assertOk()->assertJsonCount(2, 'preferences');
        $this->get(route('product-forms.public.show', ['form' => $form->slug]))
            ->assertOk()
            ->assertSee('name="preferences[]"', false)
            ->assertSee('value="'.$choices[$approved->getKey()]->getKey().'"', false)
            ->assertDontSee('value="'.$choices[$hidden->getKey()]->getKey().'"', false)
            ->assertDontSee('value="'.$choices[$foreign->getKey()]->getKey().'"', false);

        $endpoint = route('product-forms.public.submit', ['form' => $form->slug]);
        $base = ['full_name' => 'Public Person', 'email' => 'public@example.test', 'location' => 'lagos'];
        foreach ([$hidden, $foreign] as $invalidGroup) {
            $this->postJson($endpoint, $base + ['preferences' => [$choices[$invalidGroup->getKey()]->getKey()]])
                ->assertUnprocessable()->assertJsonValidationErrors('preferences');
        }
        $this->postJson($endpoint, $base)->assertUnprocessable()->assertJsonValidationErrors('preferences');
        $this->assertDatabaseCount('product_form_submissions', 0);

        $selected = [$choices[$approved->getKey()]->getKey(), $choices[$second->getKey()]->getKey()];
        $this->postJson($endpoint, $base + ['preferences' => $selected])
            ->assertCreated()->assertJsonPath('status', 'active');
        $subscriber = Subscriber::query()->where('email', 'public@example.test')->firstOrFail();
        $this->assertEqualsCanonicalizing($selected, $subscriber->subGroups()->where('purpose', 'preference')->pluck('subscriber_sub_groups.id')->all());
        $this->assertEqualsCanonicalizing($selected, ProductFormSubmission::query()->firstOrFail()->payload['preferences']);
        Mail::assertQueued(SubscriptionConfirmationMail::class, 1);

        $second->update(['is_public_preference' => false]);
        $this->postJson($endpoint, $base + ['preferences' => [$choices[$second->getKey()]->getKey()]])
            ->assertUnprocessable()->assertJsonValidationErrors('preferences');
        $this->assertDatabaseCount('product_form_submissions', 1);
    }

    public function test_subscription_form_allows_dynamic_preferences_but_rejects_foreign_fixed_audience(): void
    {
        $product = $this->createProduct();
        $foreign = $this->createProduct(['slug' => 'foreign-product']);
        $foreignGroup = SubscriberGroup::factory()->create([
            'organisation_id' => $foreign->organisation_id,
            'product_id' => $foreign->getKey(),
            'collection_handle' => $foreign->primary_collection_handle,
        ]);
        $foreignSubGroup = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $foreignGroup->getKey()]);
        $service = app(ProductFormService::class);

        $dynamic = $service->create($product, [
            'name' => 'Dynamic Signup', 'slug' => 'dynamic-signup',
            'template_family' => 'subscription_basic',
            'field_definitions' => $this->fieldDefinitions(),
        ]);
        $this->assertNull($dynamic->audience_sub_group_id);

        foreach ([['audience_sub_group_id' => $foreignSubGroup->getKey()]] as $override) {
            try {
                $service->create($product, array_merge([
                    'name' => 'Invalid Signup', 'slug' => 'invalid-signup',
                    'template_family' => 'subscription_basic',
                    'field_definitions' => $this->fieldDefinitions(),
                ], $override));
                $this->fail('Expected audience validation to reject subscription form.');
            } catch (ValidationException) {
                $this->assertDatabaseMissing('product_forms', ['slug' => 'invalid-signup']);
            }
        }

    }

    public function test_organisation_subscription_form_enrolls_approved_audiences_across_its_products_only(): void
    {
        Mail::fake();
        $first = $this->createProduct(
            ['primary_collection_handle' => 'insight_newsletters'],
            ['primary_collection_handle' => 'insight_newsletters'],
        );
        $second = $first->replicate();
        $second->fill(['name' => 'Data Dive', 'slug' => 'data-dive', 'forms_domain' => null])->save();
        $third = $first->replicate();
        $third->fill(['name' => 'Pocket Science', 'slug' => 'pocket-science', 'forms_domain' => null])->save();
        $foreign = $this->createProduct(['slug' => 'foreign', 'primary_collection_handle' => 'foundation_newsletters']);

        $choices = [];
        $groups = [];
        foreach ([$first, $second, $third, $foreign] as $product) {
            $group = SubscriberGroup::factory()->create([
                'organisation_id' => $product->organisation_id,
                'product_id' => $product->getKey(),
                'collection_handle' => $product->primary_collection_handle,
                'is_public_preference' => true,
            ]);
            $groups[$product->getKey()] = $group;
            $choices[$product->getKey()] = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->getKey()]);
        }

        $form = app(ProductFormService::class)->createForOrganisation($first->organisation, [
            'name' => 'Insight Subscription', 'slug' => 'insight-organisation-signup',
            'template_family' => 'subscription_basic', 'field_definitions' => $this->fieldDefinitions(),
            'public_preference_group_ids' => [$groups[$first->getKey()]->getKey(), $groups[$second->getKey()]->getKey()],
        ]);
        $this->assertSame('organisation', $form->form_scope);
        $this->assertNull($form->product_id);
        $this->assertNull($form->product_selection_field);
        $this->assertEqualsCanonicalizing(
            [$groups[$first->getKey()]->getKey(), $groups[$second->getKey()]->getKey()],
            $form->settings['public_preference_group_ids'],
        );

        $schemaUrl = route('product-forms.public.schema', ['form' => $form->slug]);
        $schema = $this->getJson($schemaUrl)->assertOk()->assertJsonPath('form_scope', 'organisation')->json();
        $this->assertCount(2, $schema['preferences']);
        $page = $this->get(route('product-forms.public.show', ['form' => $form->slug]))->assertOk();
        $page->assertSee('value="'.$choices[$first->getKey()]->getKey().'"', false)
            ->assertSee('value="'.$choices[$second->getKey()]->getKey().'"', false)
            ->assertDontSee('value="'.$choices[$third->getKey()]->getKey().'"', false)
            ->assertDontSee('value="'.$choices[$foreign->getKey()]->getKey().'"', false);

        $endpoint = route('product-forms.public.submit', ['form' => $form->slug]);
        $base = ['full_name' => 'Insight Reader', 'email' => 'insight-reader@example.test', 'location' => 'lagos'];
        foreach ([$third, $foreign] as $unavailable) {
            $this->postJson($endpoint, $base + ['preferences' => [$choices[$unavailable->getKey()]->getKey()]])
                ->assertUnprocessable()->assertJsonValidationErrors('preferences');
        }
        $this->assertDatabaseCount('product_form_submissions', 0);

        $selected = [$choices[$first->getKey()]->getKey(), $choices[$second->getKey()]->getKey()];
        $this->postJson($endpoint, $base + ['preferences' => $selected])
            ->assertCreated()->assertJsonPath('status', 'active');
        $submission = ProductFormSubmission::query()->firstOrFail();
        $this->assertNull($submission->product_id);
        $this->assertEqualsCanonicalizing($selected, $submission->payload['preferences']);
        $subscriber = Subscriber::query()->where('email', $base['email'])->firstOrFail();
        $this->assertSame('active', $subscriber->status);
        $this->assertEqualsCanonicalizing($selected, $subscriber->subGroups()->where('purpose', 'preference')->pluck('subscriber_sub_groups.id')->all());
        Mail::assertQueued(SubscriptionConfirmationMail::class, fn (SubscriptionConfirmationMail $mail): bool => $mail->mailConfig['product_key'] instanceof Organisation
            && (int) $mail->mailConfig['product_key']->getKey() === (int) $first->organisation_id);

        $url = app(NewsletterPublicUrlService::class)->preferencesUrl(
            $subscriber, $first->organisation->primary_collection_handle, $first->organisation,
        );
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame((string) $first->organisation_id, $query['organisation']);
        $this->assertArrayNotHasKey('product', $query);
        $this->get($url)->assertOk()
            ->assertSee('organisation='.$first->organisation_id, false)
            ->assertDontSee('product=', false);

        $this->postJson($endpoint, $base + ['preferences' => [$choices[$second->getKey()]->getKey()]])
            ->assertCreated()->assertJsonPath('subscription_status', 'subscription_updated');
        $this->assertSame('active', $subscriber->fresh()->status);
        $this->assertEqualsCanonicalizing(
            [$choices[$second->getKey()]->getKey()],
            $subscriber->fresh()->subGroups()->where('purpose', 'preference')->pluck('subscriber_sub_groups.id')->all(),
        );

        try {
            app(ProductFormService::class)->updateForOrganisation($form, $first->organisation, [
                'public_preference_group_ids' => [$groups[$foreign->getKey()]->getKey()],
            ]);
            $this->fail('A form must not offer another organisation\'s preference.');
        } catch (ValidationException) {
            $this->assertEqualsCanonicalizing(
                [$groups[$first->getKey()]->getKey(), $groups[$second->getKey()]->getKey()],
                $form->fresh()->settings['public_preference_group_ids'],
            );
        }

        app(ProductFormService::class)->updateForOrganisation($form, $first->organisation, [
            'public_preference_group_ids' => [$groups[$second->getKey()]->getKey()],
        ]);
        $this->getJson($schemaUrl)->assertOk()->assertJsonCount(1, 'preferences');
        $this->postJson($endpoint, $base + ['preferences' => [$choices[$first->getKey()]->getKey()]])
            ->assertUnprocessable()->assertJsonValidationErrors('preferences');
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

    public function test_organisation_newsletter_domain_drives_forms_and_inherited_origins(): void
    {
        /** @var ProductFormService $service */
        $service = app(ProductFormService::class);

        $product = $this->createProduct([
            'slug' => 'foundation-newsletter',
            'name' => 'Foundation Newsletter',
            'forms_domain' => null,
            'domain_status' => 'unconfigured',
            'domain_verified_at' => null,
        ], [
            'default_domain' => null,
            'source_domain' => 'dataphyte.org',
            'newsletter_subdomain' => 'nl',
            'newsletter_domain' => 'nl.dataphyte.org',
            'newsletter_domain_status' => 'verified',
            'newsletter_domain_verified_at' => now(),
        ]);

        $form = $service->create($product, [
            'name' => 'Osun Observer Application',
            'slug' => 'osun-observer-application',
            'template_family' => 'application_basic',
            'field_definitions' => $this->fieldDefinitions(),
        ]);

        $this->assertSame('https://nl.dataphyte.org/forms/osun-observer-application', $service->hostedPageUrl($form));
        $this->assertSame('https://nl.dataphyte.org/forms/osun-observer-application', $service->submitUrl($form));
        $this->assertContains('https://dataphyte.org', $service->allowedOrigins($form));
        $this->assertContains('https://www.dataphyte.org', $service->allowedOrigins($form));
        $this->assertContains('https://nl.dataphyte.org', $service->allowedOrigins($form));

        $this->withHeader('Origin', 'https://dataphyte.org')
            ->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
                'full_name' => 'Embedded Applicant',
                'email' => 'embed@example.test',
                'location' => 'lagos',
            ])
            ->assertCreated();
    }

    public function test_cp_domain_settings_derives_newsletter_domain_and_dns_instruction(): void
    {
        $this->withoutMiddleware(CountUsers::class);

        $product = $this->createProduct();
        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->scopeStatamicUserToProduct($user, $product);

        config([
            'platform.domain.newsletter_dns_target' => '203.0.113.10',
        ]);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->post(cp_route('organisation-domains.update', $product->organisation), [
                'source_domain' => 'dataphyte.org',
                'newsletter_subdomain' => 'nl',
                'newsletter_dns_record_type' => 'A',
            ])
            ->assertRedirect();

        $product->organisation->refresh();

        $this->assertSame('dataphyte.org', $product->organisation->source_domain);
        $this->assertSame('nl.dataphyte.org', $product->organisation->newsletter_domain);
        $this->assertSame('pending_verification', $product->organisation->newsletter_domain_status);

        /** @var OrganisationNewsletterDomainService $domains */
        $domains = app(OrganisationNewsletterDomainService::class);

        $this->assertSame([
            'type' => 'A',
            'name' => 'nl',
            'host' => 'nl.dataphyte.org',
            'value' => '203.0.113.10',
            'ttl' => 'Auto / 300',
        ], $domains->dnsRecord($product->organisation));
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

    public function test_application_window_closes_public_page_and_post_then_can_reopen(): void
    {
        $product = $this->createProduct();
        $form = app(ProductFormService::class)->create($product, [
            'name' => 'Windowed Application',
            'slug' => 'windowed-application',
            'template_family' => 'application_basic',
            'field_definitions' => $this->fieldDefinitions(),
            'settings' => ['accepting_submissions' => false, 'closes_at' => '2026-10-29 23:59:00'],
        ]);

        $url = route('product-forms.public.show', ['form' => $form->slug]);
        $submit = route('product-forms.public.submit', ['form' => $form->slug]);
        $this->get($url)->assertOk()->assertSee('Submissions closed');
        $this->postJson($submit, ['full_name' => 'Test Applicant'])->assertGone();

        $form->settings = ['accepting_submissions' => true, 'closes_at' => now('Africa/Lagos')->addDay()->format('Y-m-d H:i:s')];
        $form->save();
        $this->get($url)->assertOk()->assertSee('Windowed Application')->assertDontSee('Submissions closed');
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
            ->assertSee('Create Application Form')
            ->assertSee('Closes at (Africa/Lagos)')
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
                'accepting_submissions' => '1',
                'closes_at' => '2026-11-29T23:59',
            ])
            ->assertRedirect($cpBase);

        $form = ProductForm::query()->where('slug', 'operator-intake')->firstOrFail();

        $this->assertSame($product->getKey(), $form->product_id);
        $this->assertSame($product->organisation_id, $form->organisation_id);
        $this->assertSame(['https://widgets.example.test', 'https://partner.example.test'], $form->allowed_origins);
        $this->assertTrue($form->settings['accepting_submissions']);
        $this->assertSame('2026-11-29 23:59:00', $form->settings['closes_at']);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('product-forms.edit', $form))
            ->assertOk()
            ->assertSee('Edit Application Form')
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
        $this->assertFalse($form->settings['accepting_submissions']);
    }

    public function test_cp_subscription_controls_build_optional_fields_and_message_settings(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $product = $this->createProduct(
            ['primary_collection_handle' => 'insight_newsletters'],
            ['primary_collection_handle' => 'insight_newsletters'],
        );
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'collection_handle' => $product->primary_collection_handle,
            'is_public_preference' => true,
        ]);
        SubscriberSubGroup::factory()->create(['subscriber_group_id' => $group->getKey()]);
        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->scopeStatamicUserToProduct($user, $product);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->post(cp_route('subscription-forms.store'), [
                'form_scope' => 'organisation', 'organisation_id' => $product->organisation_id,
                'public_preference_group_ids' => [$group->getKey()],
                'name' => 'Signup', 'slug' => 'signup-options',
                'template_family' => 'subscription_basic', 'status' => 'published',
                'success_message' => 'Check your email.',
                'field_definitions_json' => json_encode([
                    ['handle' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ]),
                'include_country' => '1', 'include_profession' => '1', 'include_gender' => '1',
                'show_banner' => '1', 'welcome_subject' => 'Hello there',
                'welcome_body' => 'Thanks for joining.',
            ])->assertRedirect();

        $form = ProductForm::query()->where('slug', 'signup-options')->firstOrFail();
        $this->assertSame('organisation', $form->form_scope);
        $this->assertNull($form->product_id);
        $this->assertSame([$group->getKey()], $form->settings['public_preference_group_ids']);
        $this->assertSame(['email', 'country', 'profession', 'gender'], array_column($form->field_definitions, 'handle'));
        $this->assertSame('Hello there', $form->settings['welcome_subject']);
        $this->assertSame('Thanks for joining.', $form->settings['welcome_body']);
        $this->assertTrue($form->settings['show_banner']);
    }

    public function test_subscription_workspace_create_has_no_product_choice_and_rejects_product_scope(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $product = $this->createProduct();
        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->scopeStatamicUserToProduct($user, $product);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('subscription-forms.create'))
            ->assertOk()->assertSee('name="form_scope" value="organisation"', false)
            ->assertDontSee('Single Product');

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->post(cp_route('subscription-forms.store'), [
                'form_scope' => 'product', 'product_id' => $product->getKey(),
                'name' => 'Invalid Product Signup', 'slug' => 'invalid-product-signup',
                'template_family' => 'subscription_basic', 'status' => 'published',
                'success_message' => 'Thanks',
                'field_definitions_json' => json_encode([
                    ['handle' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ]),
            ])->assertStatus(422);
        $this->assertDatabaseMissing('product_forms', ['slug' => 'invalid-product-signup']);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->postJson(cp_route('subscription-forms.store'), [
                'form_scope' => 'organisation', 'organisation_id' => $product->organisation_id,
                'name' => 'No Preferences', 'slug' => 'no-preferences',
                'template_family' => 'subscription_basic', 'status' => 'published',
                'success_message' => 'Thanks',
                'field_definitions_json' => json_encode([
                    ['handle' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
                ]),
            ])->assertUnprocessable()->assertJsonValidationErrors('public_preference_group_ids');
        $this->assertDatabaseMissing('product_forms', ['slug' => 'no-preferences']);
    }

    public function test_cp_form_workspaces_reject_cross_mode_changes(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $product = $this->createProduct();
        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);
        $this->scopeStatamicUserToProduct($user, $product);

        $payload = [
            'form_scope' => 'product', 'product_id' => $product->getKey(),
            'name' => 'Boundary Check', 'slug' => 'boundary-check',
            'status' => 'published', 'success_message' => 'Thanks.',
            'field_definitions_json' => json_encode([
                ['handle' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true],
            ]),
        ];

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->post(cp_route('product-forms.store'), [...$payload, 'template_family' => 'subscription_basic'])
            ->assertStatus(422);
        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->post(cp_route('subscription-forms.store'), [...$payload, 'template_family' => 'application_basic'])
            ->assertStatus(422);
        $this->assertDatabaseMissing('product_forms', ['slug' => 'boundary-check']);
    }

    public function test_legacy_subscription_messages_can_be_managed_without_changing_its_public_route(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        $product = $this->createProduct([], ['primary_collection_handle' => 'insight_newsletters']);
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $product->organisation_id,
            'product_id' => $product->getKey(),
            'collection_handle' => 'insight_newsletters',
        ]);
        $handle = 'test_legacy_subscription_'.Str::lower(Str::random(8));
        $form = StatamicForm::make($handle)->title('Legacy Signup')->merge([
            'newsletter_group' => $group->getKey(),
            'newsletter_endpoint' => $handle,
            'newsletter_success_message' => 'Original message',
            'newsletter_send_confirmation_email' => true,
        ]);
        $form->save();

        try {
            $user = StatamicUser::findByEmail('admin@mailserver.test');
            $this->assertNotNull($user);
            $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
                ->get(cp_route('subscription-forms.legacy.edit', $handle))
                ->assertOk()->assertSee('Original message');
            $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
                ->put(cp_route('subscription-forms.legacy.update', $handle), [
                    'newsletter_success_message' => 'Thanks for joining',
                    'newsletter_confirmation_subject' => 'Welcome',
                    'newsletter_confirmation_body' => 'Your updates are coming.',
                    'newsletter_send_confirmation_email' => '1',
                ])->assertRedirect();

            $updated = StatamicForm::find($handle);
            $this->assertSame('Thanks for joining', $updated->get('newsletter_success_message'));
            $this->assertSame('Welcome', $updated->get('newsletter_confirmation_subject'));
            $this->assertSame($handle, $updated->get('newsletter_endpoint'));
        } finally {
            StatamicForm::find($handle)?->delete();
        }
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

        $academy = Organisation::query()->where('primary_collection_handle', 'academy_newsletters')->firstOrFail();
        $dataLab = Product::query()
            ->where('primary_collection_handle', 'academy_newsletters')
            ->where('blueprint_handle', 'datalab')
            ->firstOrFail();
        $insightUpdates = Product::query()
            ->where('primary_collection_handle', 'insight_newsletters')
            ->where('blueprint_handle', 'insight_updates')
            ->firstOrFail();

        $this->assertSame('Dataphyte Academy', $academy->name);
        $this->assertSame($academy->getKey(), $dataLab->organisation_id);
        $this->assertSame('DataLab', $dataLab->name);
        $this->assertSame($insight->getKey(), $insightUpdates->organisation_id);
        $this->assertSame('Insight Updates', $insightUpdates->name);

        $academy->update(['website_url' => 'https://academy.example.test', 'brand_primary_color' => '#123456']);
        $dataLab->update(['banner_path' => 'academy/banner.png']);
        $sync->sync(false, 'academy_newsletters');
        $this->assertSame('https://academy.example.test', $academy->fresh()->website_url);
        $this->assertSame('#123456', $academy->fresh()->brand_primary_color);
        $this->assertSame('academy/banner.png', $dataLab->fresh()->banner_path);
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

    private function scopeStatamicUserToProduct(User $statamicUser, Product $product): void
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
