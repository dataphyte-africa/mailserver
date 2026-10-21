<?php

namespace Tests\Feature;

use App\Jobs\Newsletter\RecomputeOrganisationEngagementJob;
use App\Models\Organisation;
use App\Models\OrganisationEngagementSetting;
use App\Models\SubscriberGroupEngagement;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Models\Subscriber;
use Illuminate\Support\Facades\Bus;
use App\Services\Newsletter\GroupEngagementService;
use Statamic\Facades\User as StatamicUser;
use Tests\TestCase;

class NewsletterEngagementRatingsTest extends TestCase
{
    public function test_cp_recalculation_interval_has_thirty_day_floor_and_manual_job(): void
    {
        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->actingAs($user, config('statamic.users.guards.cp', 'web'));
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\CountUsers::class);
        Bus::fake();

        $organisation = Organisation::query()->create([
            'name' => 'Ratings Test', 'slug' => 'ratings-test', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Ratings Product',
            'slug' => 'ratings-product', 'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'collection_handle' => 'insight_newsletters',
        ]);
        $preference = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $group->id,
            'name' => 'Legacy preference',
        ]);
        $subscriber = Subscriber::factory()->create();
        $subscriber->subGroups()->attach($preference->id, ['subscribed_at' => now()]);

        $this->get(cp_route('newsletter.groups.index'))->assertOk()
            ->assertSee('1 active subscribers')
            ->assertSee('New / Unrated')
            ->assertSee('Suppressed')
            ->assertDontSee('Legacy preference');

        $this->get(cp_route('newsletter.engagement.index', ['organisation_id' => $organisation->id]))
            ->assertOk()->assertSee('Ratings Test')->assertSee('Recalculate now');
        $this->put(cp_route('newsletter.engagement.update', $organisation), ['interval_days' => 29])
            ->assertSessionHasErrors('interval_days');
        $this->put(cp_route('newsletter.engagement.update', $organisation), ['interval_days' => 45])
            ->assertRedirect();
        $this->assertSame(45, OrganisationEngagementSetting::query()
            ->where('organisation_id', $organisation->id)->firstOrFail()->interval_days);

        $this->post(cp_route('newsletter.engagement.recalculate', $organisation))->assertRedirect();
        Bus::assertDispatched(RecomputeOrganisationEngagementJob::class,
            fn ($job) => $job->organisationId === $organisation->id);

        (new RecomputeOrganisationEngagementJob($organisation->id))->handle(app(GroupEngagementService::class));
        $this->assertSame('new', SubscriberGroupEngagement::query()
            ->where('subscriber_group_id', $group->id)->firstOrFail()->rating);
        $this->assertSame('complete', OrganisationEngagementSetting::query()
            ->where('organisation_id', $organisation->id)->firstOrFail()->last_status);
        $this->get(cp_route('newsletter.groups.index'))->assertOk()
            ->assertSee('New / Unrated:')
            ->assertDontSee('Legacy preference');
    }
}
