<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AcademySubscriptionFormTest extends TestCase
{
    public function test_academy_form_preserves_its_existing_audience_and_preferences(): void
    {
        $academy = Organisation::query()->create([
            'name' => 'Dataphyte Academy', 'slug' => 'academy-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'academy_newsletters',
            'banner_path' => 'organisations/academy/banner.gif',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $academy->id, 'name' => 'DataLab', 'slug' => 'academy-newsletters-datalab',
            'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'academy_newsletters',
        ]);
        $group = SubscriberGroup::query()->create([
            'organisation_id' => $academy->id, 'product_id' => $product->id,
            'name' => 'Dataphyte Academy', 'slug' => 'dataphyte-academy',
            'collection_handle' => 'academy_newsletters', 'audience_scope' => 'product_public',
            'is_public_preference' => false,
        ]);
        foreach (['DataLab' => 'datalab', 'Academy Update' => 'academy-update'] as $name => $slug) {
            SubscriberSubGroup::query()->create([
                'subscriber_group_id' => $group->id, 'name' => $name,
                'slug' => $slug, 'purpose' => 'preference',
            ]);
        }

        $this->artisan('newsletter:provision-academy-subscription-form')->assertSuccessful();
        $this->assertFalse($group->fresh()->is_public_preference);
        $this->assertDatabaseCount('product_forms', 0);
        $this->artisan('newsletter:provision-academy-subscription-form --apply')->assertSuccessful();
        $this->artisan('newsletter:provision-academy-subscription-form --apply')->assertSuccessful();

        $form = ProductForm::query()->where('slug', 'academy-subscribe')->sole();
        $this->assertTrue($group->fresh()->is_public_preference);
        $this->assertSame($academy->id, $form->organisation_id);
        $this->assertSame([$group->id], $form->settings['public_preference_group_ids']);
        $this->assertDatabaseCount('product_forms', 1);
        $this->get(route('product-forms.public.show', ['form' => 'academy-subscribe']))
            ->assertOk()->assertViewIs('forms.public.foundation-subscription')
            ->assertSee('<body class="subscription-theme">', false)
            ->assertSee('Keep learning with Dataphyte Academy')
            ->assertSee('DataLab')
            ->assertSee('Academy Update')
            ->assertSee('organisations/academy/banner.gif')
            ->assertDontSee('Stay Ahead with Rigorous Insights')
            ->assertDontSee('Subscribe to receive Policy Point');

        Mail::fake();
        $dataLab = $group->subGroups()->where('slug', 'datalab')->sole();
        $this->postJson(route('product-forms.public.submit', ['form' => 'academy-subscribe']), [
            'first_name' => 'Ada', 'last_name' => 'Example', 'email' => 'ada@example.test',
            'preferences' => [$dataLab->id], 'consent' => '1',
        ])->assertCreated()->assertJsonPath('subscription_status', 'subscribed');
        $subscriber = Subscriber::query()->where('email', 'ada@example.test')->sole();
        $this->assertTrue($subscriber->allSubGroups()->whereKey($dataLab->id)->exists());
        $this->assertFalse($subscriber->allSubGroups()->where('slug', 'academy-update')->exists());
    }
}
