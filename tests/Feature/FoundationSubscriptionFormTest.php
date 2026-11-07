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
