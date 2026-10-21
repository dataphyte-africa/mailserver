<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\NewsletterPublicUrlService;
use App\Services\Newsletter\GroupEngagementService;
use App\Services\Newsletter\OrganisationMailOptOut;
use App\Services\Newsletter\AudienceResolver;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrganisationPreferenceTest extends TestCase
{
    public function test_unsubscribe_shows_organisation_choices_and_partial_save_keeps_other_memberships(): void
    {
        [$insight, $dataDive] = $this->audience('Insight', 'insight_newsletters', 'Data Dive', true);
        [, $marina] = $this->audience('Insight', 'insight_newsletters', 'Marina', true, $insight);
        [$foundation, $weekly] = $this->audience('Foundation', 'foundation_newsletters', 'Weekly', true);
        $subscriber = Subscriber::factory()->create(['status' => 'active', 'confirmed_at' => now()]);
        $subscriber->allSubGroups()->attach([$dataDive->id, $marina->id, $weekly->id], ['subscribed_at' => now()]);
        $urls = app(NewsletterPublicUrlService::class);

        $response = $this->get($urls->unsubscribeUrl($subscriber, 'insight_newsletters'));
        $response->assertOk()->assertSee('Data Dive')->assertSee('Marina')
            ->assertDontSee('Weekly')->assertSee('Uncheck all');

        $this->post($urls->unsubscribeProcessUrl($subscriber, 'insight_newsletters'), [
            'sub_groups' => [$marina->id],
        ])->assertOk();

        $this->assertSame('active', $subscriber->fresh()->status);
        $this->assertEqualsCanonicalizing([$marina->id, $weekly->id], $subscriber->subGroups()->where('purpose', 'preference')->pluck('subscriber_sub_groups.id')->all());
        $this->assertDatabaseHas('organisations', ['id' => $foundation->id]);
        $this->assertSame('unsubscribed', $subscriber->groupEngagements()
            ->where('subscriber_group_id', $dataDive->subscriber_group_id)->value('rating'));
        $campaign = Campaign::factory()->create(['organisation_id' => $insight->id]);
        CampaignAudience::query()->create([
            'campaign_id' => $campaign->id, 'targetable_type' => $dataDive->group->getMorphClass(),
            'targetable_id' => $dataDive->subscriber_group_id, 'send_to_all' => true,
        ]);
        $this->assertFalse(app(AudienceResolver::class)
            ->includesSubscriber($campaign->load('audiences.targetable'), $subscriber->fresh()));
    }

    public function test_uncheck_all_removes_only_this_organisation_and_updates_global_status_when_empty(): void
    {
        [, $choice] = $this->audience('Insight', 'insight_newsletters', 'Data Dive', true);
        $subscriber = Subscriber::factory()->create(['status' => 'active', 'confirmed_at' => now()]);
        $subscriber->allSubGroups()->attach($choice->id, ['subscribed_at' => now()]);
        $url = app(NewsletterPublicUrlService::class)->unsubscribeProcessUrl($subscriber, 'insight_newsletters');

        $this->post($url, [])->assertOk()->assertSee('no remaining public product subscriptions');

        $this->assertSame('unsubscribed', $subscriber->fresh()->status);
        $this->assertFalse($subscriber->subGroups()->where('purpose', 'preference')->exists());
        $this->assertTrue($subscriber->subGroups()->where('slug', 'rating-unsubscribed')->exists());
    }

    public function test_multi_product_organisation_uses_its_verified_domain_for_preference_actions(): void
    {
        [$organisation] = $this->audience('Insight', 'insight_newsletters', 'Data Dive', true);
        $this->audience('Insight', 'insight_newsletters', 'Marina', true, $organisation);
        $organisation->update([
            'newsletter_domain' => 'nl.insight.example.test',
            'newsletter_domain_status' => 'verified',
        ]);
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $urls = app(NewsletterPublicUrlService::class);
        $url = $urls->preferencesUrl($subscriber, 'insight_newsletters');

        $this->assertStringStartsWith('https://nl.insight.example.test/preferences/', $url);
        $this->get($url)->assertOk()->assertSee('https://nl.insight.example.test/preferences/', false);
    }

    public function test_foreign_choices_cannot_be_submitted_and_unpublished_product_choice_can_be_removed(): void
    {
        [, $private] = $this->audience('Insight', 'insight_newsletters', 'Private', false);
        [, $foreign] = $this->audience('Foundation', 'foundation_newsletters', 'Weekly', true);
        $subscriber = Subscriber::factory()->create(['status' => 'active', 'confirmed_at' => now()]);
        $subscriber->allSubGroups()->attach($private->id, ['subscribed_at' => now()]);
        $urls = app(NewsletterPublicUrlService::class);

        $this->get($urls->preferencesUrl($subscriber, 'insight_newsletters'))
            ->assertOk()->assertSee('Private')->assertDontSee('Weekly');
        $this->post($urls->preferencesUpdateUrl($subscriber, 'insight_newsletters'), [
            'sub_groups' => [$private->id, $foreign->id],
        ])->assertStatus(422);
        $this->assertTrue($subscriber->subGroups()->whereKey($private->id)->exists());

        $this->post($urls->preferencesUpdateUrl($subscriber, 'insight_newsletters'), [])->assertOk();
        $this->assertFalse($subscriber->subGroups()->where('purpose', 'preference')->exists());
    }

    public function test_suppressed_address_cannot_gain_a_new_membership_through_preferences(): void
    {
        [, $choice] = $this->audience('Insight', 'insight_newsletters', 'Data Dive', true);
        $subscriber = Subscriber::factory()->create(['status' => 'complained']);
        $url = app(NewsletterPublicUrlService::class)->preferencesUpdateUrl($subscriber, 'insight_newsletters');

        $this->post($url, ['sub_groups' => [$choice->id]])->assertStatus(422);
        $this->assertSame('complained', $subscriber->fresh()->status);
        $this->assertFalse($subscriber->subGroups()->exists());
    }

    public function test_legacy_token_only_link_chooses_one_organisation_without_global_preferences(): void
    {
        [, $insight] = $this->audience('Insight', 'insight_newsletters', 'Data Dive', true);
        [, $foundation] = $this->audience('Foundation', 'foundation_newsletters', 'Weekly', true);
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $subscriber->allSubGroups()->attach([$insight->id, $foundation->id], ['subscribed_at' => now()]);

        $this->get(URL::signedRoute('newsletter.unsubscribe.show', ['token' => $subscriber->ensureConfirmationToken()]))
            ->assertOk()->assertSee('Choose an organisation')
            ->assertSee('Insight')->assertSee('Foundation')
            ->assertDontSee('Data Dive Updates')->assertDontSee('Weekly Updates');
    }

    public function test_legacy_preferences_are_not_public_product_choices_and_are_preserved(): void
    {
        [$organisation, $productChoice] = $this->audience('Insight', 'insight_newsletters', 'Data Dive', true);
        $legacy = SubscriberGroup::factory()->create([
            'organisation_id' => null, 'product_id' => null,
            'name' => 'Insight Subscribers', 'slug' => 'insight-subscribers',
            'collection_handle' => 'insight_newsletters', 'is_public_preference' => false,
        ]);
        $webinars = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $legacy->id, 'name' => 'Webinars', 'slug' => 'webinars',
        ]);
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $subscriber->allSubGroups()->attach($webinars->id, ['subscribed_at' => now()]);
        $urls = app(NewsletterPublicUrlService::class);

        $this->get($urls->preferencesUrl($subscriber, 'insight_newsletters', $organisation))
            ->assertOk()->assertDontSee('Webinars')->assertSee('Data Dive');
        $this->get($urls->preferencesUrl(Subscriber::factory()->create(), 'insight_newsletters', $organisation))
            ->assertOk()->assertDontSee('Webinars');
        $this->post($urls->preferencesUpdateUrl($subscriber, 'insight_newsletters', $organisation), [
            'sub_groups' => [$productChoice->id],
        ])->assertOk();

        $this->assertTrue($subscriber->subGroups()->whereKey($webinars->id)->exists());
        $this->assertTrue($subscriber->subGroups()->whereKey($productChoice->id)->exists());
    }

    public function test_product_choices_and_organisation_opt_out_are_separate(): void
    {
        [$organisation, $productChoice] = $this->audience('Insight', 'insight_newsletters', 'Data Dive', true);
        $privateGroup = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id,
            'product_id' => null,
            'audience_scope' => 'organisation_private',
            'is_public_preference' => false,
        ]);
        $privateChoice = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $privateGroup->id,
            'purpose' => 'preference',
            'slug' => 'members',
        ]);
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $subscriber->allSubGroups()->attach([$productChoice->id, $privateChoice->id], ['subscribed_at' => now()]);
        app(GroupEngagementService::class)->syncForSubscriber($subscriber);
        $url = app(NewsletterPublicUrlService::class)->preferencesUpdateUrl($subscriber, 'insight_newsletters', $organisation);

        $this->post($url, [])->assertOk();
        $this->assertFalse($subscriber->subGroups()->whereKey($productChoice->id)->exists());
        $this->assertTrue($subscriber->subGroups()->whereKey($privateChoice->id)->exists());
        $this->assertFalse(app(OrganisationMailOptOut::class)->has($organisation->id, $subscriber->id));

        $this->post($url, ['organisation_opt_out' => '1'])->assertOk();
        $this->assertTrue(app(OrganisationMailOptOut::class)->has($organisation->id, $subscriber->id));
        $this->assertSame('unsubscribed', $subscriber->groupEngagements()->where('subscriber_group_id', $privateGroup->id)->value('rating'));
        $this->assertTrue($subscriber->subGroups()->whereKey($privateChoice->id)->exists());

        $campaign = Campaign::factory()->create([
            'organisation_id' => $organisation->id, 'audience_scope' => 'organisation_private',
        ]);
        CampaignAudience::query()->create([
            'campaign_id' => $campaign->id, 'targetable_type' => $privateGroup->getMorphClass(),
            'targetable_id' => $privateGroup->id, 'send_to_all' => true,
        ]);
        $resolver = app(AudienceResolver::class);
        $this->assertFalse($resolver->includesSubscriber($campaign->load('audiences.targetable'), $subscriber->fresh()));
        $this->assertCount(0, $resolver->resolve($campaign));

        $this->post($url, [])->assertOk();
        $this->assertFalse(app(OrganisationMailOptOut::class)->has($organisation->id, $subscriber->id));
        $this->assertSame('new', $subscriber->groupEngagements()->where('subscriber_group_id', $privateGroup->id)->value('rating'));
    }

    public function test_unowned_legacy_preferences_are_hidden_when_collection_ownership_is_ambiguous(): void
    {
        [$organisation] = $this->audience('Insight', 'insight_newsletters', 'Data Dive', true);
        Organisation::query()->create([
            'name' => 'Other Insight', 'slug' => 'other-insight', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $legacy = SubscriberGroup::factory()->create([
            'organisation_id' => null, 'product_id' => null,
            'collection_handle' => 'insight_newsletters',
        ]);
        $webinars = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $legacy->id, 'name' => 'Webinars', 'slug' => 'webinars',
        ]);
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $subscriber->allSubGroups()->attach($webinars->id, ['subscribed_at' => now()]);

        $this->get(app(NewsletterPublicUrlService::class)
            ->preferencesUrl($subscriber, 'insight_newsletters', $organisation))
            ->assertOk()->assertDontSee('Webinars');
    }

    private function audience(string $name, string $handle, string $productName, bool $public, ?Organisation $organisation = null): array
    {
        $organisation ??= Organisation::query()->create([
            'name' => $name, 'slug' => strtolower($name), 'status' => 'active',
            'primary_collection_handle' => $handle,
        ]);
        $product = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => $productName,
            'slug' => strtolower(str_replace(' ', '-', $productName)), 'status' => 'active',
            'product_type' => 'newsletter', 'primary_collection_handle' => $handle,
        ]);
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id, 'product_id' => $product->id,
            'name' => $productName.' Subscribers', 'slug' => $product->slug.'-subscribers',
            'collection_handle' => $handle, 'is_public_preference' => $public,
        ]);
        $subGroup = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $group->id, 'name' => $productName.' Updates',
            'slug' => $product->slug.'-updates',
        ]);

        return [$organisation, $subGroup];
    }
}
