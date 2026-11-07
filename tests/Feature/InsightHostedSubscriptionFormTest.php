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
use Tests\TestCase;

class InsightHostedSubscriptionFormTest extends TestCase
{
    public function test_insight_form_uses_only_its_public_audiences_and_preserves_legacy_api(): void
    {
        $insight = Organisation::query()->create([
            'name' => 'Dataphyte Insight', 'slug' => 'insight-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'insight_newsletters',
        ]);
        $dataDive = $this->makeAudience($insight, 'Data Dive', true);
        $this->makeAudience($insight, 'Pocket Science', true);
        $this->makeAudience($insight, 'Internal', false);
        $foundation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $this->makeAudience($foundation, 'Foundation Update', true);

        $this->artisan('newsletter:provision-insight-subscription-form')->assertSuccessful();
        $this->assertDatabaseCount('product_forms', 0);
        $this->artisan('newsletter:provision-insight-subscription-form --apply')->assertSuccessful();
        $this->artisan('newsletter:provision-insight-subscription-form --apply')->assertSuccessful();

        $form = ProductForm::query()->where('slug', 'insight-subscribe')->sole();
        $this->assertSame($insight->id, $form->organisation_id);
        $this->assertCount(2, $form->settings['public_preference_group_ids']);
        $this->assertDatabaseCount('product_forms', 1);
        $this->assertStringEndsWith('/forms/insight-subscribe/submit', app(ProductFormService::class)->submitUrl($form));

        $this->get(route('product-forms.public.show', ['form' => $form->slug]))
            ->assertOk()->assertViewIs('forms.public.foundation-subscription')
            ->assertSee('<body class="subscription-theme">', false)
            ->assertSee('Explore the stories behind the data')
            ->assertSee('Data Dive')->assertSee('Pocket Science')
            ->assertSee('data-select-all-preferences', false)
            ->assertDontSee('Foundation Update')->assertDontSee('Internal');
        $this->getJson(route('product-forms.public.schema', ['form' => $form->slug]))
            ->assertOk()->assertJsonPath('endpoint', app(ProductFormService::class)->submitUrl($form))
            ->assertJsonCount(2, 'preferences');

        Mail::fake();
        $this->postJson(route('product-forms.public.submit', ['form' => $form->slug]), [
            'first_name' => 'Ada', 'last_name' => 'Example', 'email' => 'ada@example.test',
            'preferences' => [$dataDive->id], 'consent' => '1',
        ])->assertCreated()->assertJsonPath('subscription_status', 'subscribed');
        $subscriber = Subscriber::query()->where('email', 'ada@example.test')->sole();
        $this->assertTrue($subscriber->allSubGroups()->whereKey($dataDive->id)->exists());
        $this->assertFalse($subscriber->allSubGroups()->whereHas('group', fn ($query) => $query
            ->where('product_id', '!=', $dataDive->group->product_id))->exists());
    }

    private function makeAudience(Organisation $organisation, string $name, bool $public): SubscriberSubGroup
    {
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => $name,
            'slug' => $organisation->slug.'-'.str($name)->slug(),
            'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => $organisation->primary_collection_handle,
        ]);
        $group = SubscriberGroup::query()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'name' => $name.' Subscribers', 'slug' => $product->slug.'-subscribers',
            'audience_scope' => 'product_public',
            'collection_handle' => $organisation->primary_collection_handle,
            'is_public_preference' => $public,
        ]);

        return SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $group->id, 'slug' => 'newsletter',
            'name' => 'Newsletter', 'purpose' => 'preference',
        ]);
    }
}
