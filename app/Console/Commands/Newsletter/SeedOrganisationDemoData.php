<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Campaign;
use App\Models\CampaignAudience;
use App\Models\CampaignLinkClick;
use App\Models\CampaignSend;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SeedOrganisationDemoData extends Command
{
    protected $signature = 'newsletter:seed-organisation-demos';

    protected $description = 'Seed idempotent local-only Insight, Policy Point, and Foundation newsletter fixtures';

    private const PRODUCTS = [
        'insight-newsletters-data-dive' => 'Insight',
        'policy-point-newsletters-policy-point' => 'Policy Point',
        'foundation-newsletters-weekly' => 'Foundation',
    ];

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('This fixture command is restricted to local and testing environments.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function (): void {
                foreach (self::PRODUCTS as $slug => $label) {
                    $this->seedProduct($slug, $label);
                }
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Organisation demo fixtures are ready: 3 products, 6 sent campaigns, 60 subscribers, 108 sends.');
        $this->line('All addresses use @example.test; no messages were queued or sent.');

        return self::SUCCESS;
    }

    private function seedProduct(string $slug, string $label): void
    {
        $product = Product::query()->with('organisation')->where('slug', $slug)->first();
        if (! $product || $product->status !== 'active' || $product->organisation?->status !== 'active') {
            throw new RuntimeException("Active product {$slug} is missing; no fixture data was written.");
        }

        $group = SubscriberGroup::query()->where('product_id', $product->id)
            ->where('organisation_id', $product->organisation_id)
            ->where('collection_handle', $product->primary_collection_handle)
            ->whereNull('archived_at')->first();
        $subGroup = $group?->subGroups()->where('slug', 'regular')->whereNull('archived_at')->first();
        if (! $subGroup) {
            throw new RuntimeException("A non-archived Regular audience is missing for {$slug}; no fixture data was written.");
        }

        $subscribers = [];
        for ($index = 1; $index <= 20; $index++) {
            $email = sprintf('v2-demo-%s-%02d@example.test', $slug, $index);
            $status = match ($index) {
                17 => 'bounced',
                18 => 'complained',
                19 => 'pending',
                20 => 'unsubscribed',
                default => 'active',
            };
            $subscriber = Subscriber::query()->firstOrNew(['email' => $email]);
            if ($subscriber->exists && ($subscriber->metadata['fixture'] ?? null) !== 'organisation-v2') {
                throw new RuntimeException("{$email} exists outside this fixture; no fixture data was written.");
            }

            $subscriber->fill([
                'first_name' => 'Demo',
                'last_name' => sprintf('%s %02d', $label, $index),
                'status' => $status,
                'confirmed_at' => $status === 'pending' ? null : now()->subDays(15),
                'unsubscribed_at' => $status === 'unsubscribed' ? now()->subDays(3) : null,
                'pending_confirmation_expires_at' => $status === 'pending' ? now()->addDays(7) : null,
                'pending_lifecycle_state' => $status === 'pending' ? 'awaiting_confirmation' : null,
                'metadata' => ['fixture' => 'organisation-v2', 'product_slug' => $slug],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'V2 organisation demo fixture',
            ]);
            $subscriber->save();
            $subGroup->allSubscribers()->syncWithoutDetaching([
                $subscriber->id => [
                    'subscribed_at' => now()->subDays(15),
                    'unsubscribed_at' => $status === 'unsubscribed' ? now()->subDays(3) : null,
                ],
            ]);
            $subscribers[$index] = $subscriber;
        }

        foreach ([8, 2] as $campaignNumber => $daysAgo) {
            $name = "V2 Demo | {$label} | Issue ".($campaignNumber + 1);
            $subject = "[DEMO] {$label} newsletter insights ".($campaignNumber + 1);
            $campaign = Campaign::query()->firstOrNew(['name' => $name]);
            if ($campaign->exists && ($campaign->product_id !== $product->id || $campaign->subject !== $subject)) {
                throw new RuntimeException("Campaign {$name} exists outside this fixture; no fixture data was written.");
            }

            $sentAt = now()->subDays($daysAgo)->setTime(10, 0);
            $campaign->fill([
                'organisation_id' => $product->organisation_id,
                'product_id' => $product->id,
                'collection' => $product->primary_collection_handle,
                'subject' => $subject,
                'status' => 'sent',
                'sent_at' => $sentAt,
                'total_recipients' => 18,
            ]);
            $campaign->save();

            CampaignAudience::query()->firstOrCreate([
                'campaign_id' => $campaign->id,
                'targetable_type' => 'subscriber_sub_group',
                'targetable_id' => $subGroup->id,
            ], ['send_to_all' => false]);

            for ($index = 1; $index <= 18; $index++) {
                $status = match (true) {
                    $index <= 4 => 'clicked',
                    $index <= 9 => 'opened',
                    $index <= 14 => 'delivered',
                    $index <= 16 => 'failed',
                    $index === 17 => 'bounced',
                    default => 'complained',
                };
                $sendTime = $sentAt->copy()->addMinutes($index * 3);
                $deliveredAt = in_array($status, ['clicked', 'opened', 'delivered'], true)
                    ? $sendTime->copy()->addMinutes(3) : null;
                $openedAt = in_array($status, ['clicked', 'opened'], true)
                    ? $deliveredAt->copy()->addHours($index % 5 + 1) : null;
                $clickedAt = $status === 'clicked' ? $openedAt->copy()->addMinutes(8) : null;

                $send = CampaignSend::query()->updateOrCreate([
                    'campaign_id' => $campaign->id,
                    'subscriber_id' => $subscribers[$index]->id,
                ], [
                    'status' => $status,
                    'elastic_email_transaction_id' => 'v2demo-'.hash('sha256', "{$campaign->id}:{$index}"),
                    'sent_at' => $sendTime,
                    'delivered_at' => $deliveredAt,
                    'opened_at' => $openedAt,
                    'clicked_at' => $clickedAt,
                    'failed_at' => $status === 'failed' ? $sendTime->copy()->addMinutes(2) : null,
                    'bounced_at' => $status === 'bounced' ? $sendTime->copy()->addMinutes(2) : null,
                    'synced_at' => now()->subHour(),
                    'bounce_reason' => $status === 'bounced' ? 'Demo: mailbox unavailable' : null,
                ]);

                if ($clickedAt) {
                    CampaignLinkClick::query()->updateOrCreate([
                        'campaign_send_id' => $send->id,
                        'url' => 'https://example.test/demo/'.$slug.'/story-'.(($index % 2) + 1),
                    ], [
                        'clicked_at' => $clickedAt,
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'V2 organisation demo fixture',
                    ]);
                }
            }
        }

        $this->line("Seeded {$label}: {$product->name}, 20 subscribers, 2 campaigns.");
    }
}
