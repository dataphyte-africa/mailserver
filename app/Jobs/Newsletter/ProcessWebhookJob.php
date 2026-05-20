<?php

namespace App\Jobs\Newsletter;

use App\Models\CampaignLinkClick;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use App\Models\WebhookLog;
use App\Services\Newsletter\SubscriberEngagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Processes a single Elastic Email webhook event.
 *
 * Event types handled:
 *  Delivery / Delivered   → status=delivered, delivered_at
 *  Open / Opened          → opened_at (status promoted to 'opened')
 *  Click / Clicked        → clicked_at, campaign_link_clicks row
 *  Unsubscribe            → subscriber status=unsubscribed (auto-suppression)
 *  Complaint / Abuse      → subscriber status=unsubscribed + log
 *  Bounce (hard)          → status=bounced, bounced_at, subscriber auto-suppressed
 *  Bounce (soft) / Error  → status=failed, failed_at
 *
 * Lookup order:
 *  1. elastic_email_transaction_id  (most reliable)
 *  2. to_email + campaign heuristic  (fallback)
 */
class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 30;

    /**
     * Map Elastic Email event type strings → normalised internal key.
     * Elastic Email uses inconsistent casing/naming across versions.
     */
    private const EVENT_MAP = [
        // Delivery
        'delivery'         => 'delivered',
        'delivered'        => 'delivered',
        // Open
        'open'             => 'opened',
        'opened'           => 'opened',
        // Click
        'click'            => 'clicked',
        'clicked'          => 'clicked',
        'linkclicked'      => 'clicked',
        // Unsubscribe
        'unsubscribe'      => 'unsubscribed',
        'unsubscribed'     => 'unsubscribed',
        // Complaint / Abuse
        'complaint'        => 'complained',
        'abuse'            => 'complained',
        'abusereport'      => 'complained',
        'spamreport'       => 'complained',
        // Hard bounce
        'bouncedhard'      => 'bounced',
        'bounce'           => 'bounced',
        'bounced'          => 'bounced',
        'hardbouncedmail'  => 'bounced',
        // Soft bounce / errors
        'bouncedsoft'      => 'failed',
        'error'            => 'failed',
        'failed'           => 'failed',
        'sendingfailed'    => 'failed',
    ];

    public function __construct(public readonly int $webhookLogId) {}

    /* ------------------------------------------------------------------ */

    public function handle(): void
    {
        $log = WebhookLog::find($this->webhookLogId);

        if (! $log) {
            Log::warning("ProcessWebhookJob: WebhookLog {$this->webhookLogId} not found");
            return;
        }

        try {
            $this->process($log);
            $log->markProcessed();
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());
            Log::error("ProcessWebhookJob: failed for log {$log->id} — {$e->getMessage()}");
            throw $e;
        }
    }

    /* ------------------------------------------------------------------ */

    private function process(WebhookLog $log): void
    {
        $event = $this->normalise($log->event_type);

        if (! $event) {
            Log::debug("ProcessWebhookJob: unknown event '{$log->event_type}' — skipping");
            return;
        }

        // Resolve the CampaignSend
        $send = $this->resolveSend($log);

        if (! $send) {
            // We may receive events for emails sent outside this system — ignore
            Log::debug("ProcessWebhookJob: no CampaignSend for tx={$log->transaction_id} email={$log->to_email}");
            return;
        }

        match ($event) {
            'delivered'  => $this->handleDelivered($send, $log),
            'opened'     => $this->handleOpened($send, $log),
            'clicked'    => $this->handleClicked($send, $log),
            'unsubscribed','complained' => $this->handleSuppression($send, $log, $event),
            'bounced'    => $this->handleBounce($send, $log, hard: true),
            'failed'     => $this->handleBounce($send, $log, hard: false),
            default      => null,
        };

        if ($send->subscriber) {
            app(SubscriberEngagementService::class)->persist($send->subscriber);
        }
    }

    /* ------------------------------------------------------------------ */

    private function handleDelivered(CampaignSend $send, WebhookLog $log): void
    {
        // Only update if not already at a further-along status
        if (in_array($send->status, ['opened', 'clicked'])) {
            return;
        }

        $updates = [
            'status' => 'delivered',
            'bounce_reason' => null,
            'failed_at' => null,
            'bounced_at' => null,
        ];
        $eventDate = $this->eventDate($log);
        $this->markSyncedIfApplicable($updates, $log);

        if ($eventDate && ! $send->delivered_at) {
            $updates['delivered_at'] = $eventDate;
        }

        $send->update($updates);
    }

    private function handleOpened(CampaignSend $send, WebhookLog $log): void
    {
        $updates = [
            'bounce_reason' => null,
            'failed_at' => null,
            'bounced_at' => null,
        ];
        $eventDate = $this->eventDate($log);
        $this->markSyncedIfApplicable($updates, $log);

        if (! $send->opened_at && $eventDate) {
            $updates['opened_at'] = $eventDate;
        }

        if (! in_array($send->status, ['opened', 'clicked'])) {
            $updates['status'] = 'opened';
        }

        // Also mark delivered if not already
        if (! $send->delivered_at && $eventDate) {
            $updates['delivered_at'] = $eventDate;
        }

        if ($updates) {
            $send->update($updates);
        }
    }

    private function handleClicked(CampaignSend $send, WebhookLog $log): void
    {
        $clickedAt = $this->eventDate($log);
        $url       = $this->extractField($log->payload, ['link', 'Link', 'clickedlink', 'ClickedLink', 'url', 'URL']);

        $updates = [
            'bounce_reason' => null,
            'failed_at' => null,
            'bounced_at' => null,
        ];
        $this->markSyncedIfApplicable($updates, $log);

        if (! $send->clicked_at && $clickedAt) {
            $updates['clicked_at'] = $clickedAt;
        }

        if (! $send->opened_at && $clickedAt) {
            $updates['opened_at'] = $clickedAt;
        }

        if (! in_array($send->status, ['clicked'])) {
            $updates['status'] = 'clicked';
        }

        if (! $send->delivered_at && $clickedAt) {
            $updates['delivered_at'] = $clickedAt;
        }

        if ($updates) {
            $send->update($updates);
        }

        // Record individual link click (allow multiple per send)
        if ($url && $clickedAt) {
            CampaignLinkClick::create([
                'campaign_send_id' => $send->id,
                'url'              => $url,
                'clicked_at'       => $clickedAt,
                'ip_address'       => $this->extractField($log->payload, ['ipaddress', 'IPAddress', 'ip', 'ip_address']),
                'user_agent'       => $this->extractField($log->payload, ['useragent', 'UserAgent', 'user_agent']),
            ]);
        }
    }

    private function handleSuppression(CampaignSend $send, WebhookLog $log, string $event): void
    {
        // Mark the send
        $sendUpdates = ['status' => $event === 'complained' ? 'complained' : 'delivered'];
        $this->markSyncedIfApplicable($sendUpdates, $log);
        $send->update($sendUpdates);

        // Suppress the subscriber globally
        $subscriber = $send->subscriber;
        if ($subscriber && $subscriber->status !== 'unsubscribed') {
            $updates = ['status' => 'unsubscribed'];
            $eventDate = $this->eventDate($log);

            if ($eventDate) {
                $updates['unsubscribed_at'] = $eventDate;
            }

            $subscriber->update($updates);

            Log::info("Subscriber {$subscriber->email} auto-suppressed via {$event} webhook");
        }
    }

    private function handleBounce(CampaignSend $send, WebhookLog $log, bool $hard): void
    {
        $reason = $this->extractField($log->payload, ['bounceerror', 'BounceError', 'bounce_error', 'error', 'Error', 'message']);

        if ($hard) {
            $updates = [
                'status'       => 'bounced',
                'bounce_reason'=> $reason,
            ];
            $eventDate = $this->eventDate($log);
            $this->markSyncedIfApplicable($updates, $log);

            if ($eventDate) {
                $updates['bounced_at'] = $eventDate;
            }

            $send->update($updates);

            // Hard bounce → permanent suppression
            $subscriber = $send->subscriber;
            if ($subscriber && $subscriber->status === 'active') {
                $subscriber->update(['status' => 'bounced']);
                Log::info("Subscriber {$subscriber->email} suppressed after hard bounce");
            }
        } else {
            $updates = [
                'status'       => 'failed',
                'bounce_reason'=> $reason,
            ];
            $eventDate = $this->eventDate($log);
            $this->markSyncedIfApplicable($updates, $log);

            if ($eventDate) {
                $updates['failed_at'] = $eventDate;
            }

            $send->update($updates);
        }
    }

    /* ------------------------------------------------------------------ */

    private function resolveSend(WebhookLog $log): ?CampaignSend
    {
        // Priority 1: send_id custom field set by ElasticEmailTransport on every
        // outgoing email — Elastic Email echoes recipient fields in webhook payloads.
        // This is the most reliable match (no ambiguity, works even when Elastic
        // Email omits their own TransactionID from the payload).
        $sendId = $this->extractField($log->payload, ['send_id']);
        if ($sendId) {
            $send = CampaignSend::with('subscriber')->find((int) $sendId);
            if ($send) return $send;
        }

        // Priority 2: Elastic Email's own TransactionID stored at send time.
        if ($log->transaction_id) {
            $send = CampaignSend::where('elastic_email_transaction_id', $log->transaction_id)
                ->with('subscriber')
                ->first();

            if ($send) return $send;
        }

        // Fallback: most recent send to this email in a sending/sent campaign.
        if ($log->to_email) {
            return CampaignSend::whereHas('subscriber', fn ($q) => $q->where('email', $log->to_email))
                ->whereHas('campaign', fn ($q) => $q->whereIn('status', ['sending', 'sent']))
                ->with('subscriber')
                ->latest('sent_at')
                ->first();
        }

        return null;
    }

    private function normalise(?string $rawEvent): ?string
    {
        if (! $rawEvent) return null;
        $key = strtolower(preg_replace('/[\s_\-]/', '', $rawEvent));
        return self::EVENT_MAP[$key] ?? null;
    }

    private function eventDate(WebhookLog $log): ?\Carbon\Carbon
    {
        $raw = $this->extractField($log->payload, ['date', 'Date', 'timestamp', 'Timestamp', 'eventdate', 'EventDate']);
        return $raw ? \Carbon\Carbon::parse($raw) : null;
    }

    private function markSyncedIfApplicable(array &$updates, WebhookLog $log): void
    {
        if (($log->payload['_source'] ?? null) === 'sync-command') {
            $updates['synced_at'] = now();
        }
    }

    private function extractField(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && (string) $payload[$key] !== '') {
                return (string) $payload[$key];
            }
        }
        return null;
    }
}
