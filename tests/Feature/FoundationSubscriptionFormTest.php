<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\ProductFormSubmission;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Forms\ProductFormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FoundationSubscriptionFormTest extends TestCase
{
    public function test_foundation_form_offers_only_its_public_products_and_requires_consent(): void
    {
        $foundation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $this->makePublicAudience($foundation, 'Foundation Update', 'foundation-newsletters-weekly', 'foundation-update-subscribers');
        $this->makePublicAudience($foundation, 'Policy Point', 'foundation-newsletters-policy-point', 'foundation-policy-point-subscribers');
        $other = Organisation::query()->create([
            'name' => 'Policy Point', 'slug' => 'policy-point-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'policy_point_newsletters',
        ]);
        $this->makePublicAudience($other, 'Policy Point', 'policy-point-newsletters-policy-point', 'policy-point-subscribers');

        $this->artisan('newsletter:provision-foundation-subscription-form')->assertSuccessful();
        $this->assertDatabaseCount('product_forms', 0);
        $this->artisan('newsletter:provision-foundation-subscription-form --apply')->assertSuccessful();
        $this->artisan('newsletter:provision-foundation-subscription-form --apply')->assertSuccessful();

        $form = ProductForm::query()->where('slug', 'foundation-subscribe')->sole();
        $this->assertSame($foundation->id, $form->organisation_id);
        $this->assertSame('organisation', $form->form_scope);
        $this->assertCount(2, $form->settings['public_preference_group_ids']);
        $this->assertSame(['first_name', 'last_name', 'email', 'country', 'organisation', 'profession', 'consent'],
            array_column($form->field_definitions, 'handle'));

        $page = $this->get(route('product-forms.public.show', ['form' => 'foundation-subscribe']))->assertOk();
        $page->assertViewIs('forms.public.foundation-subscription')
            ->assertSee('Stay Ahead with Rigorous Insights')
            ->assertSee('name="consent"', false)
            ->assertSee('Subscribe to receive Foundation Update')
            ->assertSee('Subscribe to receive Policy Point')
            ->assertSee('data-select-all-preferences', false)
            ->assertSee('Subscribe to Newsletters')
            ->assertDontSee('policy-point-newsletters-policy-point');

        $foundation->update(['banner_path' => 'organisations/foundation/banner.gif']);
        $this->get(route('product-forms.public.show', ['form' => 'foundation-subscribe']))
            ->assertOk()
            ->assertSee('organisations/foundation/banner.gif')
            ->assertDontSee('assets/foundation/dataphyte-foundation-logo.png');

        $preference = SubscriberSubGroup::query()->whereHas('group', fn ($query) => $query
            ->where('slug', 'foundation-update-subscribers'))->where('purpose', 'preference')->sole();
        $payload = [
            'first_name' => 'Ada', 'last_name' => 'Example', 'email' => 'ada@example.test',
            'preferences' => [$preference->id],
        ];
        try {
            app(ProductFormService::class)->storeSubmission($form, $payload, Request::create('/forms/foundation-subscribe', 'POST', $payload));
            $this->fail('A signup without consent must fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('consent', $exception->errors());
        }
        $this->assertDatabaseCount('product_form_submissions', 0);

        Mail::fake();
        $this->withMiddleware();
        $response = $this->postJson(route('product-forms.public.submit', ['form' => 'foundation-subscribe']), [
            ...$payload,
            'country' => 'NGA', 'organisation' => 'Dataphyte', 'profession' => 'Researcher',
            'consent' => '1',
        ])->assertCreated()->assertJsonPath('subscription_status', 'subscribed');
        $subscriber = Subscriber::query()->where('email', 'ada@example.test')->sole();
        $this->assertSame('active', $subscriber->status);
        $this->assertSame('Ada', $subscriber->first_name);
        $this->assertTrue($subscriber->allSubGroups()->whereKey($preference->id)->exists());
        $this->assertFalse($subscriber->allSubGroups()->whereHas('group', fn ($query) => $query
            ->where('organisation_id', $other->id))->exists());
        $this->assertSame('Dataphyte', ProductFormSubmission::query()
            ->findOrFail($response->json('submission_id'))->payload['organisation']);

        $storedFields = $form->field_definitions;
        $storedFields[0]['required'] = true;
        $storedFields[1]['required'] = true;
        $form->update(['field_definitions' => $storedFields]);
        $this->assertTrue(json_decode($form->getRawOriginal('field_definitions'), true)[0]['required']);
        $schema = $this->getJson(route('product-forms.public.schema', ['form' => 'foundation-subscribe']))
            ->assertOk()->json();
        $this->assertFalse(collect($schema['fields'])->firstWhere('handle', 'first_name')['required']);
        $this->assertFalse(collect($schema['fields'])->firstWhere('handle', 'last_name')['required']);
        $page = $this->get(route('product-forms.public.show', ['form' => 'foundation-subscribe']))->assertOk();
        $page->assertDontSee('name="first_name"', false)
            ->assertSee('name="email"', false);

        $this->postJson(route('product-forms.public.submit', ['form' => 'foundation-subscribe']), [
            'email' => 'nameless@example.test', 'preferences' => [$preference->id], 'consent' => '1',
        ])->assertCreated()->assertJsonPath('subscription_status', 'subscribed');
        $this->assertNull(Subscriber::query()->where('email', 'nameless@example.test')->sole()->first_name);

        $submissionCount = ProductFormSubmission::query()->count();
        $this->postJson(route('product-forms.public.submit', ['form' => 'foundation-subscribe']), [
            'email' => 'ADA@example.test', 'preferences' => [$preference->id], 'consent' => '1',
        ])->assertOk()->assertJsonPath('status', 'management_link_sent');
        $this->assertSame($submissionCount, ProductFormSubmission::query()->count());
        $this->assertSame('Ada', $subscriber->fresh()->first_name);
        $this->assertSame('Example', $subscriber->last_name);

        $this->post(route('product-forms.public.submit', ['form' => 'foundation-subscribe']), [
            'email' => 'browser@example.test', 'preferences' => [$preference->id], 'consent' => '1',
        ])->assertRedirect();
        $submissionCount = ProductFormSubmission::query()->count();
        $modal = $this->get(route('product-forms.public.show', ['form' => 'foundation-subscribe']))->assertOk();
        $modal->assertSee('role="dialog"', false)
            ->assertSee('Thank you for joining Foundation Update.')
            ->assertSee('name="first_name"', false)
            ->assertSee('Save details')->assertSee('Skip for now');

        $this->post(route('product-forms.public.profile', ['form' => 'foundation-subscribe']), [
            'action' => 'save', 'first_name' => 'Bola', 'last_name' => 'Reader',
            'country' => 'NGA', 'profession' => 'Analyst',
        ])->assertRedirect();
        $this->assertSame($submissionCount, ProductFormSubmission::query()->count());
        $browserSubscriber = Subscriber::query()->where('email', 'browser@example.test')->sole();
        $this->assertSame('Bola', $browserSubscriber->first_name);
        $this->assertSame('Reader', $browserSubscriber->last_name);
        $this->assertSame('Analyst', $browserSubscriber->metadata['reader_profiles'][$foundation->id]['profession']);
        $this->assertSame('NGA', ProductFormSubmission::query()->latest('id')->firstOrFail()->payload['country']);
        $this->get(route('product-forms.public.show', ['form' => 'foundation-subscribe']))
            ->assertOk()->assertDontSee('role="dialog"', false);
        $this->post(route('product-forms.public.profile', ['form' => 'foundation-subscribe']), [
            'action' => 'save', 'first_name' => 'Changed',
        ])->assertForbidden();

        $this->post(route('product-forms.public.submit', ['form' => 'foundation-subscribe']), [
            'email' => 'skip@example.test', 'preferences' => [$preference->id], 'consent' => '1',
        ])->assertRedirect();
        $this->post(route('product-forms.public.profile', ['form' => 'foundation-subscribe']), [
            'action' => 'skip',
        ])->assertRedirect();
        $this->assertNull(Subscriber::query()->where('email', 'skip@example.test')->sole()->first_name);
    }

    public function test_subscription_listing_and_csv_show_latest_email_once_with_selected_preferences(): void
    {
        $foundation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $this->makePublicAudience($foundation, 'Foundation Update', 'foundation-newsletters-weekly', 'foundation-update-subscribers');
        $this->artisan('newsletter:provision-foundation-subscription-form --apply')->assertSuccessful();
        $form = ProductForm::query()->where('slug', 'foundation-subscribe')->sole();
        $preference = SubscriberSubGroup::query()->whereHas('group', fn ($query) => $query
            ->where('slug', 'foundation-update-subscribers'))->sole();

        $older = app(ProductFormService::class)->storeSubmission($form, [
            'email' => 'Ada@Example.test', 'preferences' => [$preference->id], 'consent' => '1',
        ], Request::create('/forms/foundation-subscribe/submit', 'POST'));
        $repeat = app(ProductFormService::class)->storeSubmission($form, [
            'email' => 'ada@example.test', 'preferences' => [$preference->id], 'consent' => '1',
        ], Request::create('/forms/foundation-subscribe/submit', 'POST'));
        $this->assertSame($older->id, $repeat->id);
        $this->assertSame(1, ProductFormSubmission::query()->count());

        $newer = $older->replicate();
        $newer->payload = ['email' => 'ADA@example.test', 'preferences' => [$preference->id], 'consent' => '1'];
        $newer->submitted_at = now()->addSecond();
        $newer->save();

        $service = app(ProductFormService::class);
        $listing = $service->submissions($form);
        $this->assertSame(1, $listing->total());
        $this->assertSame($newer->id, $listing->items()[0]->id);
        $this->assertSame('Foundation Update / Newsletter', $service->selectedPreferenceLabels($listing->items())[$newer->id]);
        $this->assertSame(2, ProductFormSubmission::query()->count());

        $csv = $service->csvContent($form);
        $this->assertStringContainsString('selected_preferences', $csv);
        $this->assertStringContainsString('Foundation Update / Newsletter', $csv);
        $this->assertStringNotContainsString('ada@example.test', $csv);
        $this->assertStringContainsString('ADA@example.test', $csv);
    }

    private function makePublicAudience(Organisation $organisation, string $name, string $productSlug, string $groupSlug): void
    {
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => $name, 'slug' => $productSlug,
            'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => $organisation->primary_collection_handle,
        ]);
        $group = SubscriberGroup::query()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'name' => $name.' Subscribers', 'slug' => $groupSlug,
            'audience_scope' => 'product_public',
            'collection_handle' => $organisation->primary_collection_handle,
            'is_public_preference' => true,
        ]);
        SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $group->id, 'slug' => 'newsletter',
            'name' => 'Newsletter', 'purpose' => 'preference',
        ]);
    }
}
