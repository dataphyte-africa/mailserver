<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Forms\ProductFormService;
use Illuminate\Support\Facades\Mail;
use Statamic\Facades\Form;
use Tests\TestCase;

class PolicyPointHostedSubscriptionFormTest extends TestCase
{
    public function test_policy_point_upgrade_keeps_historical_memberships_and_frequency_choices(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Policy Point', 'slug' => 'policy-point', 'status' => 'active',
            'primary_collection_handle' => 'policy_point_newsletters',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Policy Point',
            'slug' => 'policy-point-newsletters-policy-point', 'status' => 'active',
            'product_type' => 'newsletter', 'primary_collection_handle' => 'policy_point_newsletters',
        ]);
        $foundation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $foundationProduct = Product::query()->create([
            'organisation_id' => $foundation->id, 'name' => 'Policy Point',
            'slug' => 'foundation-newsletters-policy-point', 'status' => 'active',
            'product_type' => 'newsletter', 'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $foundationGroup = SubscriberGroup::query()->create([
            'organisation_id' => $foundation->id, 'product_id' => $foundationProduct->id,
            'name' => 'Policy Point Subscribers', 'slug' => 'foundation-policy-point-subscribers',
            'collection_handle' => 'foundation_newsletters', 'audience_scope' => 'product_public',
            'is_public_preference' => false,
        ]);
        $group = SubscriberGroup::query()->create([
            'name' => 'Policy Point Subscribers', 'slug' => 'policy-point-subscribers',
            'collection_handle' => 'policy_point_newsletters', 'audience_scope' => 'product_public',
            'is_public_preference' => false,
        ]);
        $regular = $this->frequency($group, 'Regular', 'regular');
        $monthly = $this->frequency($group, 'monthly', 'monthly');
        Form::make('policy_point_subscribe')->title('Policy Point Subscribe')->store(true)->merge([
            'newsletter_group' => $group->id,
            'newsletter_endpoint' => 'policy-point',
            'newsletter_preference_field' => 'preference',
        ])->save();
        $subscriber = Subscriber::factory()->create(['email' => 'existing@example.test', 'status' => 'active']);
        $subscriber->subGroups()->attach($regular->id, ['subscribed_at' => now()]);

        $this->artisan('newsletter:provision-policy-point-subscription-form')->assertSuccessful();
        $this->assertNull($group->fresh()->product_id);
        $this->assertDatabaseCount('product_forms', 0);
        $this->artisan('newsletter:provision-policy-point-subscription-form --apply')->assertSuccessful();
        $this->artisan('newsletter:provision-policy-point-subscription-form --apply')->assertSuccessful();

        $this->assertSame($organisation->id, $group->fresh()->organisation_id);
        $this->assertSame($product->id, $group->fresh()->product_id);
        $this->assertTrue($group->fresh()->is_public_preference);
        $this->assertFalse($foundationGroup->fresh()->is_public_preference);
        $this->assertSame($foundation->id, $foundationGroup->fresh()->organisation_id);
        $this->assertTrue($subscriber->fresh()->allSubGroups()->whereKey($regular->id)->exists());
        $this->assertSame($group->id, $regular->fresh()->subscriber_group_id);
        $this->assertSame($group->id, $monthly->fresh()->subscriber_group_id);
        $this->assertDatabaseCount('product_forms', 1);

        $form = ProductForm::query()->where('slug', 'policy-point-subscribe')->sole();
        $this->assertSame([$group->id], $form->settings['public_preference_group_ids']);
        $this->get(route('product-forms.public.show', ['form' => $form->slug]))
            ->assertOk()->assertViewIs('forms.public.foundation-subscription')
            ->assertSee('Stay informed with Policy Point')->assertSee('Delivery frequency')
            ->assertSee('type="radio"', false)->assertSee('Regular')->assertSee('Monthly');
        $this->getJson(route('product-forms.public.schema', ['form' => $form->slug]))
            ->assertOk()->assertJsonPath('endpoint', app(ProductFormService::class)->submitUrl($form))
            ->assertJsonCount(1, 'preferences')->assertJsonCount(2, 'preferences.0.sub_groups');
        $this->get(route('newsletter.forms.show', ['form' => 'policy-point']))
            ->assertRedirect(app(ProductFormService::class)->hostedPageUrl($form));

        Mail::fake();
        $payload = [
            'first_name' => 'Ada', 'last_name' => 'Example', 'email' => 'ada@example.test',
            'preferences' => [$regular->id, $monthly->id], 'consent' => '1',
        ];
        $this->postJson(route('product-forms.public.submit', ['form' => $form->slug]), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('preferences');
        $this->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
            ...$payload, 'preferences' => [$monthly->id],
        ])->assertCreated()->assertJsonPath('subscription_status', 'subscribed');
        $new = Subscriber::query()->where('email', 'ada@example.test')->sole();
        $this->assertTrue($new->allSubGroups()->whereKey($monthly->id)->exists());
        $this->assertFalse($new->allSubGroups()->whereKey($regular->id)->exists());
        $this->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
            ...$payload, 'email' => 'existing@example.test', 'preferences' => [$monthly->id],
        ])->assertCreated()->assertJsonPath('subscription_status', 'subscription_updated');
        $this->assertTrue($subscriber->fresh()->subGroups()->whereKey($monthly->id)->exists());
        $this->assertFalse($subscriber->fresh()->subGroups()->whereKey($regular->id)->exists());
    }

    private function frequency(SubscriberGroup $group, string $name, string $slug): SubscriberSubGroup
    {
        return SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $group->id, 'name' => $name,
            'slug' => $slug, 'purpose' => 'preference',
        ]);
    }
}
