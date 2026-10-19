<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\EntryModel;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\SubscriberGroup;
use App\Services\Platform\StatamicNewsletterProductSyncService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Statamic\Facades\Blueprint;
use Statamic\Facades\Collection as StatamicCollection;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class AcademyNewsletterMigrationTest extends TestCase
{
    public function test_super_admin_can_select_academy_and_insight_updates_in_newsletter_studio(): void
    {
        $this->withoutMiddleware(CountUsers::class);
        app(StatamicNewsletterProductSyncService::class)->sync();
        $user = StatamicUser::findByEmail('admin@mailserver.test');
        $this->assertNotNull($user);

        $this->actingAs($user, config('statamic.users.guards.cp', 'web'))
            ->get(cp_route('newsletter.entries.create'))
            ->assertOk()
            ->assertSee('Dataphyte Academy')
            ->assertSee('DataLab')
            ->assertSee('Insight Updates');
    }

    public function test_checked_in_blueprint_sync_replaces_one_definition_without_changing_entries(): void
    {
        StatamicCollection::make('academy_newsletters')->title('Academy Newsletters')->save();
        Blueprint::make('datalab')->setNamespace('collections.academy_newsletters')
            ->setContents(['title' => 'Old DataLab', 'tabs' => []])->save();
        $entry = EntryModel::query()->create([
            'site' => 'default', 'collection' => 'academy_newsletters', 'blueprint' => 'datalab',
            'slug' => 'existing-academy-issue', 'date' => '2026-10-19', 'published' => true,
            'data' => ['title' => 'Existing Academy Issue', 'subject' => 'Existing subject'],
        ]);

        $this->assertSame(0, Artisan::call('newsletter:sync-checked-in-blueprint', [
            'collection' => 'academy_newsletters', 'blueprint' => 'datalab',
        ]));

        $contents = Blueprint::find('collections.academy_newsletters.datalab')->contents();
        $fields = collect($contents['tabs']['main']['sections'][0]['fields'])->pluck('handle');
        $this->assertSame('DataLab', $contents['title']);
        $this->assertContains('hero_image_override_url', $fields);
        $this->assertContains('cta_url', $fields);
        $this->assertSame('Existing subject', $entry->fresh()->data['subject']);
    }

    public function test_collection_filtered_backfill_assigns_only_academy_legacy_rows(): void
    {
        $academy = Organisation::query()->create([
            'name' => 'Dataphyte Academy', 'slug' => 'academy-newsletters',
            'status' => 'active', 'primary_collection_handle' => 'academy_newsletters',
        ]);
        $dataLab = Product::query()->create([
            'organisation_id' => $academy->getKey(), 'name' => 'DataLab',
            'slug' => 'academy-newsletters-datalab', 'status' => 'active',
            'product_type' => 'newsletter', 'primary_collection_handle' => 'academy_newsletters',
            'blueprint_handle' => 'datalab',
        ]);
        $academyGroup = SubscriberGroup::factory()->create([
            'name' => 'Dataphyte Academy', 'slug' => 'dataphyte-academy',
            'collection_handle' => 'academy_newsletters',
            'organisation_id' => $academy->getKey(), 'product_id' => $dataLab->getKey(),
        ]);
        $academyCampaign = Campaign::factory()->create([
            'name' => 'Academy Draft', 'collection' => 'academy_newsletters',
            'organisation_id' => $academy->getKey(), 'product_id' => $dataLab->getKey(),
        ]);
        $other = SubscriberGroup::factory()->create([
            'name' => 'Insight Legacy', 'slug' => 'insight-legacy',
            'collection_handle' => 'insight_newsletters',
        ]);

        DB::table('subscriber_groups')->whereIn('id', [$academyGroup->getKey(), $other->getKey()])
            ->update(['organisation_id' => null, 'product_id' => null]);
        DB::table('campaigns')->where('id', $academyCampaign->getKey())
            ->update(['organisation_id' => null, 'product_id' => null]);

        $this->assertSame(0, Artisan::call('newsletter:backfill-historical-ownership', [
            '--collection' => 'academy_newsletters', '--apply' => true,
        ]));

        $this->assertDatabaseHas('subscriber_groups', [
            'id' => $academyGroup->getKey(), 'organisation_id' => $academy->getKey(),
            'product_id' => $dataLab->getKey(),
        ]);
        $this->assertDatabaseHas('campaigns', [
            'id' => $academyCampaign->getKey(), 'organisation_id' => $academy->getKey(),
            'product_id' => $dataLab->getKey(),
        ]);
        $this->assertDatabaseHas('subscriber_groups', [
            'id' => $other->getKey(), 'organisation_id' => null, 'product_id' => null,
        ]);
    }
}
