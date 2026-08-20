<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistoricalOwnershipAuditCommandTest extends TestCase
{
    public function test_command_reports_findings_without_mutating_database_rows(): void
    {
        $group = SubscriberGroup::factory()->create([
            'name' => 'Audit Group',
            'slug' => 'audit-group',
            'collection_handle' => 'insight_newsletters',
            'organisation_id' => null,
            'product_id' => null,
        ]);

        $subGroup = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $group->id,
            'name' => 'Audit Sub-group',
            'slug' => 'audit-sub-group',
        ]);

        $campaign = Campaign::factory()->create([
            'collection' => 'insight_newsletters',
            'organisation_id' => null,
            'product_id' => null,
        ]);

        $subscriber = Subscriber::factory()->active()->create([
            'email' => 'audit-orphan@example.test',
        ]);

        DB::table('campaign_audiences')->insert([
            'campaign_id' => $campaign->id,
            'targetable_type' => 'subscriber_sub_group',
            'targetable_id' => $subGroup->id + 999,
            'send_to_all' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $before = $this->snapshotTables();
        $path = tempnam(sys_get_temp_dir(), 'historical-ownership-audit-');

        $this->artisan('newsletter:audit-historical-ownership', [
            '--json' => true,
            '--output' => $path,
        ])->assertExitCode(0);

        $after = $this->snapshotTables();
        $report = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        unlink($path);

        $this->assertSame($before, $after);
        $this->assertTrue($report['database']['unchanged']);
        $this->assertSame(1, $report['records']['campaign_audiences']['missing_targets_count']);
        $this->assertSame(1, $report['records']['subscribers']['without_any_membership_count']);
        $this->assertSame(1, $report['records']['subscriber_groups']['affected_count']);
        $this->assertSame(1, $report['records']['campaigns']['affected_count']);
    }

    public function test_backfill_command_applies_only_deterministic_campaign_ownership(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Insight',
            'slug' => 'insight-newsletters',
            'status' => 'active',
        ]);

        $dataDive = Product::query()->create([
            'organisation_id' => $organisation->id,
            'name' => 'Data Dive',
            'slug' => 'insight-newsletters-data-dive',
            'status' => 'active',
            'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters',
            'blueprint_handle' => 'data_dive',
        ]);

        Product::query()->create([
            'organisation_id' => $organisation->id,
            'name' => 'Pocket Science',
            'slug' => 'insight-newsletters-pocket-science',
            'status' => 'active',
            'product_type' => 'newsletter',
            'primary_collection_handle' => 'insight_newsletters',
            'blueprint_handle' => 'pocket_science',
        ]);

        $group = SubscriberGroup::factory()->create([
            'collection_handle' => 'insight_newsletters',
            'organisation_id' => null,
            'product_id' => null,
        ]);

        $subGroup = SubscriberSubGroup::factory()->create([
            'subscriber_group_id' => $group->id,
            'slug' => 'data-dive',
        ]);

        $mappable = Campaign::factory()->create([
            'collection' => 'insight_newsletters',
            'organisation_id' => null,
            'product_id' => null,
        ]);

        $ambiguous = Campaign::factory()->create([
            'collection' => 'insight_newsletters',
            'organisation_id' => null,
            'product_id' => null,
        ]);

        DB::table('campaign_audiences')->insert([
            'campaign_id' => $mappable->id,
            'targetable_type' => 'subscriber_sub_group',
            'targetable_id' => $subGroup->id,
            'send_to_all' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('newsletter:backfill-historical-ownership', ['--apply' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('campaigns', [
            'id' => $mappable->id,
            'organisation_id' => $organisation->id,
            'product_id' => $dataDive->id,
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $ambiguous->id,
            'organisation_id' => null,
            'product_id' => null,
        ]);
    }

    private function snapshotTables(): array
    {
        $tables = [
            'subscriber_groups',
            'subscriber_sub_groups',
            'subscribers',
            'campaigns',
            'campaign_audiences',
        ];

        $snapshot = [];

        foreach ($tables as $table) {
            $snapshot[$table] = DB::table($table)
                ->orderBy('id')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();
        }

        return $snapshot;
    }
}
