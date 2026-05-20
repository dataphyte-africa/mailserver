<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\CampaignLinkClick;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeedDemoCampaign extends Command
{
    protected $signature = 'newsletter:seed-demo-campaign
                            {--fresh : Delete the existing demo campaign before reseeding}
                            {--collection=policy_point_newsletters : Collection handle for the seeded campaign}';

    protected $description = 'Seed a realistic sent campaign with analytics-friendly local demo data';

    private const DEMO_NAME = 'Local Analytics Demo — Policy Point';

    public function handle(): int
    {
        $collection = (string) $this->option('collection');

        DB::transaction(function () use ($collection) {
            if ($this->option('fresh')) {
                Campaign::where('name', self::DEMO_NAME)->delete();
            }

            $group = $this->resolveGroup($collection);
            $subGroup = $this->resolveSubGroup($group);

            $campaign = Campaign::updateOrCreate(
                ['name' => self::DEMO_NAME],
                [
                    'collection' => $collection,
                    'subject' => 'Local demo: Electricity policy and student entrepreneurship',
                    'from_name' => null,
                    'from_email' => null,
                    'reply_to' => null,
                    'status' => 'sent',
                    'sent_at' => now()->subDays(2)->setTime(8, 0),
                    'total_recipients' => 60,
                    'created_by' => null,
                ]
            );

            $campaign->audiences()->delete();
            CampaignAudience::create([
                'campaign_id' => $campaign->id,
                'targetable_type' => 'subscriber_group',
                'targetable_id' => $group->id,
                'send_to_all' => false,
            ]);
            CampaignAudience::create([
                'campaign_id' => $campaign->id,
                'targetable_type' => 'subscriber_sub_group',
                'targetable_id' => $subGroup->id,
                'send_to_all' => false,
            ]);

            CampaignLinkClick::whereIn('campaign_send_id', $campaign->sends()->pluck('id'))->delete();
            $campaign->sends()->delete();

            $statuses = array_merge(
                array_fill(0, 12, 'clicked'),
                array_fill(0, 18, 'opened'),
                array_fill(0, 22, 'delivered'),
                array_fill(0, 4, 'failed'),
                array_fill(0, 4, 'bounced')
            );

            $clickUrls = [
                'https://dataphyte.com/policy-point/electricity-student-entrepreneurship',
                'https://dataphyte.com/policy-point/power-universities-data',
                'https://dataphyte.com/policy-point/energy-education-reform',
                'https://dataphyte.com/policy-point/policy-brief-download',
            ];

            foreach ($statuses as $index => $status) {
                $subscriber = Subscriber::updateOrCreate(
                    ['email' => sprintf('local-demo-%02d@dataphyte.test', $index + 1)],
                    [
                        'first_name' => fake()->firstName(),
                        'last_name' => fake()->lastName(),
                        'status' => 'active',
                        'confirmation_token' => (string) Str::uuid(),
                        'confirmed_at' => now()->subDays(3),
                        'unsubscribed_at' => null,
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Local Demo Seeder',
                        'metadata' => [
                            'seeded_demo' => true,
                            'campaign' => self::DEMO_NAME,
                        ],
                    ]
                );

                $subGroup->allSubscribers()->syncWithoutDetaching([
                    $subscriber->id => [
                        'subscribed_at' => now()->subDays(7),
                        'unsubscribed_at' => null,
                    ],
                ]);

                $sentAt = now()->subDays(2)->setTime(8, 0)->addMinutes($index * 4);
                $deliveredAt = $sentAt->copy()->addMinutes(rand(1, 12));
                $openedAt = null;
                $clickedAt = null;
                $failedAt = null;
                $bouncedAt = null;
                $bounceReason = null;

                if (in_array($status, ['opened', 'clicked'], true)) {
                    $openedAt = $deliveredAt->copy()->addHours(($index % 12))->addMinutes(rand(0, 45));
                }

                if ($status === 'clicked') {
                    $clickedAt = ($openedAt ?? $deliveredAt)->copy()->addMinutes(rand(1, 20));
                    if (! $openedAt) {
                        $openedAt = $clickedAt->copy();
                    }
                }

                if ($status === 'failed') {
                    $failedAt = $sentAt->copy()->addMinutes(rand(2, 10));
                    $bounceReason = fake()->randomElement([
                        'Mailbox unavailable',
                        'Exceeded storage allocation',
                        'Temporary DNS failure',
                    ]);
                }

                if ($status === 'bounced') {
                    $bouncedAt = $sentAt->copy()->addMinutes(rand(2, 10));
                    $bounceReason = fake()->randomElement([
                        'mailbox does not exist',
                        'recipient rejected by destination server',
                    ]);
                }

                $send = CampaignSend::create([
                    'campaign_id' => $campaign->id,
                    'subscriber_id' => $subscriber->id,
                    'status' => $status,
                    'elastic_email_transaction_id' => (string) Str::uuid(),
                    'sent_at' => $sentAt,
                    'delivered_at' => in_array($status, ['delivered', 'opened', 'clicked'], true) ? $deliveredAt : null,
                    'opened_at' => $openedAt,
                    'clicked_at' => $clickedAt,
                    'bounced_at' => $bouncedAt,
                    'failed_at' => $failedAt,
                    'synced_at' => now()->subHours(rand(1, 24)),
                    'bounce_reason' => $bounceReason,
                ]);

                if ($status === 'clicked' && $clickedAt) {
                    $clickCount = ($index % 3) + 1;

                    for ($clickIndex = 0; $clickIndex < $clickCount; $clickIndex++) {
                        CampaignLinkClick::create([
                            'campaign_send_id' => $send->id,
                            'url' => $clickUrls[($index + $clickIndex) % count($clickUrls)],
                            'clicked_at' => $clickedAt->copy()->addMinutes($clickIndex),
                            'ip_address' => '127.0.0.1',
                            'user_agent' => 'Local Demo Seeder',
                        ]);
                    }
                }
            }

            $updates = [
                'total_recipients' => 60,
            ];

            if (Schema::hasColumns('campaigns', [
                'last_stats_sync_requested_at',
                'last_stats_sync_completed_at',
                'last_stats_sync_status',
                'last_stats_sync_total',
                'last_stats_sync_processed',
                'last_stats_sync_error',
            ])) {
                $updates = array_merge($updates, [
                    'last_stats_sync_requested_at' => now()->subMinutes(12),
                    'last_stats_sync_completed_at' => now()->subMinutes(10),
                    'last_stats_sync_status' => 'completed',
                    'last_stats_sync_total' => 60,
                    'last_stats_sync_processed' => 60,
                    'last_stats_sync_error' => null,
                ]);
            }

            $campaign->forceFill($updates)->save();
        });

        $this->info('Demo campaign seeded successfully.');
        $this->line('Campaign: ' . self::DEMO_NAME);
        $this->line('Collection: ' . $collection);
        $this->line('Recipients: 60');
        $this->line('Mix: 12 clicked, 18 opened, 22 delivered, 4 failed, 4 bounced');

        return self::SUCCESS;
    }

    private function resolveGroup(string $collection): SubscriberGroup
    {
        $defaults = match ($collection) {
            'policy_point_newsletters' => ['name' => 'Policy Point Subscribers', 'slug' => 'policy-point-subscribers'],
            'insight_newsletters' => ['name' => 'Insight Subscribers', 'slug' => 'insight-subscribers'],
            'foundation_newsletters' => ['name' => 'Foundation', 'slug' => 'foundation'],
            default => ['name' => 'Demo Subscribers', 'slug' => 'demo-subscribers'],
        };

        return SubscriberGroup::firstOrCreate(
            ['slug' => $defaults['slug']],
            [
                'name' => $defaults['name'],
                'collection_handle' => $collection,
                'description' => 'Seeded local demo audience',
            ]
        );
    }

    private function resolveSubGroup(SubscriberGroup $group): SubscriberSubGroup
    {
        return SubscriberSubGroup::firstOrCreate(
            [
                'subscriber_group_id' => $group->id,
                'slug' => 'regular',
            ],
            [
                'name' => 'Regular',
                'description' => 'Seeded local demo subgroup',
            ]
        );
    }
}
