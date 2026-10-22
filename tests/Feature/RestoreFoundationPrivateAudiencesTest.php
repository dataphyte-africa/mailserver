<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\User as StatamicUser;
use Tests\TestCase;

class RestoreFoundationPrivateAudiencesTest extends TestCase
{
    public function test_restores_named_private_audiences_without_changing_legacy_memberships(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation', 'slug' => 'foundation', 'status' => 'active',
            'primary_collection_handle' => 'foundation_newsletters',
        ]);
        $legacy = SubscriberGroup::factory()->foundation()->create();
        $weekly = SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $legacy->id, 'name' => 'Weekly Update', 'slug' => 'weekly',
            'purpose' => 'preference',
        ]);
        $observers = SubscriberSubGroup::query()->create([
            'subscriber_group_id' => $legacy->id, 'name' => 'Osun Election Observers 2026',
            'slug' => 'osun-election-observers-2026', 'purpose' => 'preference',
        ]);
        $overlap = Subscriber::factory()->create(['status' => 'active']);
        $unsubscribed = Subscriber::factory()->create(['status' => 'unsubscribed']);
        $overlap->allSubGroups()->attach([$weekly->id, $observers->id], ['subscribed_at' => now()->subMonth()]);
        $unsubscribed->allSubGroups()->attach($weekly->id, [
            'subscribed_at' => now()->subMonth(), 'unsubscribed_at' => now()->subWeek(),
        ]);
        DB::table('subscriber_group_engagements')->insert([
            'subscriber_id' => $overlap->id, 'subscriber_group_id' => $legacy->id,
            'rating' => 'moderate', 'is_member' => true, 'score' => 5,
            'first_joined_at' => now()->subMonth(), 'last_recalculated_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('newsletter:restore-foundation-private-audiences')->assertSuccessful();
        $this->assertSame(1, SubscriberGroup::query()->count());

        $this->artisan('newsletter:restore-foundation-private-audiences --apply')->assertSuccessful();
        $this->artisan('newsletter:restore-foundation-private-audiences --apply')->assertSuccessful();

        $this->assertNull($legacy->fresh()->organisation_id);
        $this->assertSame(3, DB::table('subscriber_sub_group')
            ->whereIn('subscriber_sub_group_id', [$weekly->id, $observers->id])->count());
        foreach (['weekly' => 2, 'osun-election-observers-2026' => 1] as $slug => $count) {
            $group = SubscriberGroup::query()->where('slug', 'foundation-private-'.$slug)->sole();
            $this->assertSame($organisation->id, $group->organisation_id);
            $this->assertSame('organisation_private', $group->audience_scope);
            $this->assertNull($group->product_id);
            $members = $group->subGroups()->where('slug', 'members')->sole();
            $this->assertSame($count, DB::table('subscriber_sub_group')
                ->where('subscriber_sub_group_id', $members->id)->count());
            $this->assertSame(7, $group->subGroups()->where('purpose', 'engagement')->count());
            $this->assertSame('moderate', DB::table('subscriber_group_engagements')
                ->where('subscriber_group_id', $group->id)
                ->where('subscriber_id', $overlap->id)->value('rating'));
        }
        $weeklyGroup = SubscriberGroup::query()->where('slug', 'foundation-private-weekly')->sole();
        $weeklyMembers = $weeklyGroup->subGroups()->where('slug', 'members')->sole();
        $this->assertNotNull(DB::table('subscriber_sub_group')
            ->where('subscriber_sub_group_id', $weeklyMembers->id)
            ->where('subscriber_id', $unsubscribed->id)->value('unsubscribed_at'));
        $this->assertSame('unsubscribed', DB::table('subscriber_group_engagements')
            ->where('subscriber_group_id', $weeklyGroup->id)
            ->where('subscriber_id', $unsubscribed->id)->value('rating'));

        $operator = StatamicUser::findByEmail('admin@mailserver.test');
        $this->actingAs($operator, config('statamic.users.guards.cp', 'web'));
        $this->withoutMiddleware(\Statamic\Http\Middleware\CP\CountUsers::class);
        $this->get(cp_route('newsletter.groups.index'))->assertOk()
            ->assertSee('Weekly Update')->assertSee('Osun Election Observers 2026')
            ->assertDontSee('Historical audience');

        $this->artisan('newsletter:migrate-foundation-audiences --apply')->assertFailed();
    }
}
