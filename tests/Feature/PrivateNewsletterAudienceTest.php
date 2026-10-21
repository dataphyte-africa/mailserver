<?php

namespace Tests\Feature;

use App\Exceptions\Newsletter\CampaignAudienceOwnershipException;
use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\CampaignSend;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\AudienceResolver;
use App\Services\Newsletter\CampaignAudienceOwnershipService;
use App\Services\Newsletter\NewsletterPublicUrlService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Statamic\Facades\User as StatamicUser;
use Tests\TestCase;

class PrivateNewsletterAudienceTest extends TestCase
{
    public function test_private_group_has_no_product_and_members_are_not_public_preferences(): void
    {
        $this->actingAs(StatamicUser::findByEmail('admin@mailserver.test'), config('statamic.users.guards.cp', 'web'));
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\CountUsers::class);
        [$organisation] = $this->organisationAndTemplate();

        $this->post(cp_route('newsletter.groups.store'), [
            'organisation_id' => $organisation->id,
            'name' => 'Private Research Audience',
        ])->assertRedirect();

        $group = SubscriberGroup::query()->where('name', 'Private Research Audience')->firstOrFail();
        $this->assertNull($group->product_id);
        $this->assertSame('organisation_private', $group->audience_scope);
        $this->assertFalse($group->is_public_preference);
        $this->assertSame(8, $group->subGroups()->count());

        $this->post(cp_route('newsletter.groups.members.store', $group), [
            'email' => 'private@example.test',
            'source' => 'Research event signup',
            'permission_confirmed' => '1',
        ])->assertRedirect();
        $subscriber = Subscriber::query()->where('email', 'private@example.test')->firstOrFail();
        $this->get(app(NewsletterPublicUrlService::class)->preferencesUrl($subscriber, $organisation->primary_collection_handle, $organisation))
            ->assertOk()->assertDontSee('Private Research Audience');
        $this->get(cp_route('newsletter.groups.edit', $group))->assertOk()->assertSee('private@example.test');
        $this->assertSame('Research event signup', $subscriber->allSubGroups()
            ->where('subscriber_group_id', $group->id)->firstOrFail()->pivot->private_source);
    }

    public function test_private_csv_import_skips_existing_opt_outs(): void
    {
        $this->actingAs(StatamicUser::findByEmail('admin@mailserver.test'), config('statamic.users.guards.cp', 'web'));
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\CountUsers::class);
        [$organisation] = $this->organisationAndTemplate();
        $group = $this->privateGroup($organisation);
        $members = $group->subGroups()->where('slug', 'members')->firstOrFail();
        $optedOut = Subscriber::factory()->create(['email' => 'left@example.test', 'status' => 'active']);
        $optedOut->allSubGroups()->attach($members->id, [
            'subscribed_at' => now()->subDay(), 'unsubscribed_at' => now(),
        ]);

        $this->post(cp_route('newsletter.groups.members.import', $group), [
            'csv_file' => UploadedFile::fake()->createWithContent('members.csv', "email,first_name,last_name\nnew@example.test,New,Reader\nleft@example.test,Former,Reader\n"),
            'source' => 'Conference registration',
            'permission_confirmed' => '1',
        ])->assertRedirect()->assertSessionHas('success', 'Import complete: 1 added, 1 skipped (including duplicates and opt-outs).');

        $new = Subscriber::query()->where('email', 'new@example.test')->firstOrFail();
        $this->assertTrue($new->subGroups()->whereKey($members->id)->exists());
        $this->assertFalse($optedOut->subGroups()->whereKey($members->id)->exists());
    }

    public function test_oversized_private_csv_import_does_not_partially_add_members(): void
    {
        $this->actingAs(StatamicUser::findByEmail('admin@mailserver.test'), config('statamic.users.guards.cp', 'web'));
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\CountUsers::class);
        [$organisation] = $this->organisationAndTemplate();
        $group = $this->privateGroup($organisation);

        $rows = "email\n";
        for ($number = 1; $number <= 2001; $number++) {
            $rows .= "reader{$number}@example.test\n";
        }

        $this->post(cp_route('newsletter.groups.members.import', $group), [
            'csv_file' => UploadedFile::fake()->createWithContent('members.csv', $rows),
            'source' => 'Conference registration',
            'permission_confirmed' => '1',
        ])->assertSessionHasErrors('csv_file');

        $this->assertSame(0, Subscriber::query()->where('email', 'reader1@example.test')->count());
    }

    public function test_queued_private_send_is_skipped_after_member_leaves(): void
    {
        Mail::fake();
        [$organisation, $template] = $this->organisationAndTemplate();
        $group = $this->privateGroup($organisation);
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $campaign = Campaign::factory()->create([
            'organisation_id' => $organisation->id,
            'product_id' => $template->id,
            'collection' => $template->primary_collection_handle,
            'audience_scope' => 'organisation_private',
        ]);
        CampaignAudience::query()->create([
            'campaign_id' => $campaign->id,
            'targetable_type' => $group->getMorphClass(),
            'targetable_id' => $group->id,
            'send_to_all' => true,
        ]);
        $send = CampaignSend::factory()->for($campaign)->for($subscriber)->create(['status' => 'queued']);

        (new \App\Jobs\Newsletter\SendNewsletterEmailJob($send->id))->handle();

        $this->assertSame('skipped', $send->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_private_targeting_excludes_suppressed_and_rejects_foreign_groups(): void
    {
        [$organisation, $template] = $this->organisationAndTemplate();
        $group = $this->privateGroup($organisation);
        $memberSegment = $group->subGroups()->where('slug', 'members')->firstOrFail();
        $active = Subscriber::factory()->create(['status' => 'active']);
        $blocked = Subscriber::factory()->create(['status' => 'active']);
        foreach ([$active, $blocked] as $subscriber) {
            $subscriber->subGroups()->attach($memberSegment->id, ['subscribed_at' => now()]);
        }
        app(\App\Services\Newsletter\GroupEngagementService::class)->sync($active, $group);
        app(\App\Services\Newsletter\GroupEngagementService::class)->sync($blocked, $group);
        $blocked->groupEngagements()->where('subscriber_group_id', $group->id)->update(['rating' => 'suppressed']);

        $campaign = Campaign::factory()->create([
            'organisation_id' => $organisation->id,
            'product_id' => $template->id,
            'collection' => $template->primary_collection_handle,
            'audience_scope' => 'organisation_private',
        ]);
        $audiences = app(CampaignAudienceOwnershipService::class);
        $audiences->replace($campaign, $audiences->validateForPrivate($template, $group->id, true, []));

        $resolved = app(AudienceResolver::class)->resolve($campaign->load('audiences'));
        $this->assertEqualsCanonicalizing([$active->id], $resolved->modelKeys());
        $this->assertTrue(app(AudienceResolver::class)->includesPrivateMember($campaign, $active));
        $this->assertFalse(app(AudienceResolver::class)->includesPrivateMember($campaign, $blocked));

        $foreign = Organisation::query()->create(['name' => 'Other', 'slug' => 'other-private', 'status' => 'active']);
        $foreignGroup = $this->privateGroup($foreign);
        $this->expectException(CampaignAudienceOwnershipException::class);
        $audiences->validateForPrivate($template, $foreignGroup->id, true, []);
    }

    public function test_private_campaign_without_persisted_audience_fails_closed(): void
    {
        [$organisation, $template] = $this->organisationAndTemplate();
        $campaign = Campaign::factory()->create([
            'organisation_id' => $organisation->id,
            'product_id' => $template->id,
            'collection' => $template->primary_collection_handle,
            'audience_scope' => 'organisation_private',
        ]);

        $this->expectException(CampaignAudienceOwnershipException::class);
        app(CampaignAudienceOwnershipService::class)->validatePersistedForPrivate($campaign, $template);
    }

    public function test_private_rating_subgroup_can_be_targeted_but_suppressed_cannot(): void
    {
        [$organisation, $template] = $this->organisationAndTemplate();
        $group = $this->privateGroup($organisation);
        app(\App\Services\Newsletter\GroupEngagementService::class)->ensureSegments($group);
        $members = $group->subGroups()->where('slug', 'members')->firstOrFail();
        $high = $group->subGroups()->where('slug', 'rating-high')->firstOrFail();
        $suppressed = $group->subGroups()->where('slug', 'rating-suppressed')->firstOrFail();
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $subscriber->subGroups()->attach($members->id, ['subscribed_at' => now()]);
        app(\App\Services\Newsletter\GroupEngagementService::class)->sync($subscriber, $group);
        $subscriber->groupEngagements()->where('subscriber_group_id', $group->id)->update(['rating' => 'high']);
        $subscriber->subGroups()->attach($high->id, ['subscribed_at' => now()]);

        $campaign = Campaign::factory()->create([
            'organisation_id' => $organisation->id,
            'product_id' => $template->id,
            'collection' => $template->primary_collection_handle,
            'audience_scope' => 'organisation_private',
        ]);
        $audiences = app(CampaignAudienceOwnershipService::class);
        $audiences->replace($campaign, $audiences->validateForPrivate($template, $group->id, false, [$high->id]));

        $this->assertEqualsCanonicalizing([$subscriber->id], app(AudienceResolver::class)->resolve($campaign->load('audiences'))->modelKeys());
        $this->assertTrue(app(AudienceResolver::class)->includesPrivateMember($campaign, $subscriber));

        $this->expectException(CampaignAudienceOwnershipException::class);
        $audiences->validateForPrivate($template, $group->id, false, [$suppressed->id]);
    }

    public function test_private_unsubscribe_keeps_public_product_membership(): void
    {
        [$organisation, $template] = $this->organisationAndTemplate();
        $private = $this->privateGroup($organisation);
        $members = $private->subGroups()->where('slug', 'members')->firstOrFail();
        $public = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id, 'product_id' => $template->id,
            'collection_handle' => $template->primary_collection_handle,
            'is_public_preference' => true,
        ]);
        $choice = SubscriberSubGroup::factory()->create(['subscriber_group_id' => $public->id]);
        $subscriber = Subscriber::factory()->create(['status' => 'active']);
        $subscriber->subGroups()->attach([$members->id, $choice->id], ['subscribed_at' => now()]);
        $campaign = Campaign::factory()->create([
            'organisation_id' => $organisation->id, 'product_id' => $template->id,
            'collection' => $template->primary_collection_handle,
            'audience_scope' => 'organisation_private',
        ]);
        CampaignAudience::query()->create([
            'campaign_id' => $campaign->id,
            'targetable_type' => $private->getMorphClass(),
            'targetable_id' => $private->id,
            'send_to_all' => true,
        ]);
        $urls = app(NewsletterPublicUrlService::class);
        $this->get($urls->unsubscribeUrl($subscriber, $organisation->primary_collection_handle, $campaign))
            ->assertOk()->assertSee('Leave '.$private->name);
        $this->post($urls->unsubscribeProcessUrl($subscriber, $organisation->primary_collection_handle, $organisation, $private->id))
            ->assertOk()->assertSee('You have left '.$private->name);

        $this->assertFalse($subscriber->subGroups()->whereKey($members->id)->exists());
        $this->assertTrue($subscriber->subGroups()->whereKey($choice->id)->exists());
        $this->assertSame('active', $subscriber->fresh()->status);
    }

    public function test_cp_can_create_private_campaign_with_insight_updates_template(): void
    {
        $this->actingAs(StatamicUser::findByEmail('admin@mailserver.test'), config('statamic.users.guards.cp', 'web'));
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\CountUsers::class);
        [$organisation, $template] = $this->organisationAndTemplate();
        $group = $this->privateGroup($organisation);

        $this->get(cp_route('newsletter.campaigns.create'))->assertOk()
            ->assertSee('Private organisation audience')->assertSee($group->name);
        $this->post(cp_route('newsletter.campaigns.store'), [
            'product_id' => $template->id,
            'audience_scope' => 'organisation_private',
            'private_group_id' => $group->id,
            'name' => 'Private insight digest',
            'collection' => 'insight_newsletters',
            'subject' => 'Research update',
            'send_to_all' => '1',
            'action' => 'draft',
        ])->assertRedirect();

        $campaign = Campaign::query()->where('name', 'Private insight digest')->firstOrFail();
        $this->assertSame('organisation_private', $campaign->audience_scope);
        $this->assertSame($template->id, $campaign->product_id);
        $this->assertSame($group->id, $campaign->audiences()->firstOrFail()->targetable_id);
        $this->get(cp_route('newsletter.campaigns.edit', $campaign))->assertOk()
            ->assertSee('Private organisation audience');
    }

    private function organisationAndTemplate(): array
    {
        $organisation = Organisation::query()->create([
            'name' => 'Private Insight', 'slug' => 'private-insight', 'status' => 'active',
            'primary_collection_handle' => 'insight_newsletters',
        ]);
        $template = Product::query()->create([
            'organisation_id' => $organisation->id, 'name' => 'Insight Updates',
            'slug' => 'private-insight-updates', 'status' => 'active', 'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters', 'blueprint_handle' => 'insight_updates',
        ]);

        return [$organisation, $template];
    }

    private function privateGroup(Organisation $organisation): SubscriberGroup
    {
        $group = SubscriberGroup::factory()->create([
            'organisation_id' => $organisation->id,
            'product_id' => null,
            'audience_scope' => 'organisation_private',
            'collection_handle' => null,
            'is_public_preference' => false,
        ]);
        SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $group->id,
            'name' => 'Members', 'slug' => 'members', 'purpose' => 'preference',
        ]);

        return $group;
    }
}
