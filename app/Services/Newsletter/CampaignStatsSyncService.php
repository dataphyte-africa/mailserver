<?php

namespace App\Services\Newsletter;

use App\Jobs\Newsletter\ProcessWebhookJob;
use App\Models\CampaignSend;
use Illuminate\Database\Eloquent\Builder;
use App\Models\WebhookLog;
use ElasticEmail\Api\EmailsApi;
use ElasticEmail\Api\EventsApi;
use ElasticEmail\Configuration;
use ElasticEmail\Model\EmailData;
use ElasticEmail\Model\EmailStatus;
use ElasticEmail\Model\EventType;
use ElasticEmail\Model\EventsOrderBy;
use ElasticEmail\Model\RecipientEvent;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class CampaignStatsSyncService
{
    public function sync(
        ?int $campaignId = null,
        ?int $hours = null,
        int $days = 30,
        int $limit = 0,
        bool $dryRun = false,
        bool $applyWindow = true,
        ?callable $onProgress = null,
    ): array {
        $apiKey = config('mail.mailers.elasticemail.key');

        if (empty($apiKey)) {
            return [
                'ok' => false,
                'error' => 'ELASTIC_EMAIL_API_KEY is not set. Cannot sync stats.',
            ];
        }

        $emailsApi = $this->buildEmailsApi($apiKey);
        $eventsApi = $this->buildEventsApi($apiKey);

        $query = $this->eligibleSendsQuery($campaignId, $hours, $days, $limit, $applyWindow);

        $total = $query->count();

        if ($total === 0) {
            if ($onProgress) {
                $onProgress(0, 0);
            }

            return [
                'ok' => true,
                'total' => 0,
                'synced' => 0,
                'window' => $applyWindow ? ($hours ? "{$hours}h" : "{$days}d") : 'unbounded',
            ];
        }

        $synced = 0;
        $processed = 0;
        $errors = [];

        if ($onProgress) {
            $onProgress(0, $total);
        }

        foreach ($query->cursor() as $send) {
            $send->loadMissing(['campaign', 'subscriber']);

            try {
                $result = $emailsApi->emailsByTransactionidStatusGet(
                    $send->elastic_email_transaction_id,
                    show_failed: true,
                    show_sent: true,
                    show_delivered: true,
                    show_pending: true,
                    show_opened: true,
                    show_clicked: true,
                    show_abuse: true,
                    show_unsubscribed: true,
                    show_errors: true,
                    show_message_ids: true,
                );

                if (! $result) {
                    continue;
                }

                $status = $this->normaliseStatusFromJob($result);

                if (! $status) {
                    continue;
                }

                $priority = ['failed' => 0, 'delivered' => 1, 'opened' => 2, 'clicked' => 3];
                $current = $priority[$send->status] ?? -1;
                $incoming = $priority[$status] ?? -1;

                if ($incoming <= $current) {
                    continue;
                }

                [$eventType, $eventDate] = $this->resolveBackfillEvent(
                    $emailsApi,
                    $eventsApi,
                    $send,
                    $result,
                    $status,
                );

                $bounceReason = $this->extractFailureReason($result);

                if ($dryRun) {
                    $synced++;
                    continue;
                }

                $payload = [
                    'EventType' => $eventType,
                    'TransactionID' => $send->elastic_email_transaction_id,
                    'To' => $send->subscriber?->email,
                    'BounceError' => $bounceReason,
                    '_source' => 'sync-command',
                ];

                if ($eventDate) {
                    $payload['Date'] = $eventDate->toIso8601String();
                }

                $log = WebhookLog::create([
                    'event_type' => $eventType,
                    'transaction_id' => $send->elastic_email_transaction_id,
                    'to_email' => $send->subscriber?->email,
                    'payload' => $payload,
                ]);

                ProcessWebhookJob::dispatch($log->id)->onQueue('webhooks');
                $synced++;
            } catch (\Throwable $e) {
                $errors[] = "send #{$send->id}: {$e->getMessage()}";
                Log::warning("CampaignStatsSyncService: send #{$send->id} — {$e->getMessage()}");
            } finally {
                $processed++;

                if ($onProgress) {
                    $onProgress($processed, $total);
                }
            }
        }

        return [
            'ok' => true,
            'total' => $total,
            'synced' => $synced,
            'processed' => $processed,
            'errors' => $errors,
            'window' => $applyWindow ? ($hours ? "{$hours}h" : "{$days}d") : 'unbounded',
        ];
    }

    public function eligibleSendIds(
        ?int $campaignId = null,
        ?int $hours = null,
        int $days = 30,
        int $limit = 0,
        bool $applyWindow = true,
    ): array {
        return $this->eligibleSendsQuery($campaignId, $hours, $days, $limit, $applyWindow)
            ->pluck('id')
            ->all();
    }

    public function syncSendIds(array $sendIds, bool $dryRun = false): array
    {
        $apiKey = config('mail.mailers.elasticemail.key');

        if (empty($apiKey)) {
            return [
                'ok' => false,
                'error' => 'ELASTIC_EMAIL_API_KEY is not set. Cannot sync stats.',
            ];
        }

        $sendIds = array_values(array_unique(array_filter(array_map('intval', $sendIds))));

        if ($sendIds === []) {
            return [
                'ok' => true,
                'total' => 0,
                'synced' => 0,
                'processed' => 0,
                'errors' => [],
                'window' => 'manual-chunk',
            ];
        }

        $emailsApi = $this->buildEmailsApi($apiKey);
        $eventsApi = $this->buildEventsApi($apiKey);

        $query = CampaignSend::query()
            ->whereIn('id', $sendIds)
            ->whereIn('status', ['sent', 'pending', 'delivered', 'opened'])
            ->whereNotNull('elastic_email_transaction_id')
            ->orderBy('sent_at', 'asc');

        $total = $query->count();

        if ($total === 0) {
            return [
                'ok' => true,
                'total' => 0,
                'synced' => 0,
                'processed' => 0,
                'errors' => [],
                'window' => 'manual-chunk',
            ];
        }

        $synced = 0;
        $processed = 0;
        $errors = [];

        foreach ($query->cursor() as $send) {
            $send->loadMissing(['campaign', 'subscriber']);

            try {
                $result = $emailsApi->emailsByTransactionidStatusGet(
                    $send->elastic_email_transaction_id,
                    show_failed: true,
                    show_sent: true,
                    show_delivered: true,
                    show_pending: true,
                    show_opened: true,
                    show_clicked: true,
                    show_abuse: true,
                    show_unsubscribed: true,
                    show_errors: true,
                    show_message_ids: true,
                );

                if (! $result) {
                    continue;
                }

                $status = $this->normaliseStatusFromJob($result);

                if (! $status) {
                    continue;
                }

                $priority = ['failed' => 0, 'delivered' => 1, 'opened' => 2, 'clicked' => 3];
                $current = $priority[$send->status] ?? -1;
                $incoming = $priority[$status] ?? -1;

                if ($incoming <= $current) {
                    continue;
                }

                [$eventType, $eventDate] = $this->resolveBackfillEvent(
                    $emailsApi,
                    $eventsApi,
                    $send,
                    $result,
                    $status,
                );

                $bounceReason = $this->extractFailureReason($result);

                if ($dryRun) {
                    $synced++;
                    continue;
                }

                $payload = [
                    'EventType' => $eventType,
                    'TransactionID' => $send->elastic_email_transaction_id,
                    'To' => $send->subscriber?->email,
                    'BounceError' => $bounceReason,
                    '_source' => 'sync-command',
                ];

                if ($eventDate) {
                    $payload['Date'] = $eventDate->toIso8601String();
                }

                $log = WebhookLog::create([
                    'event_type' => $eventType,
                    'transaction_id' => $send->elastic_email_transaction_id,
                    'to_email' => $send->subscriber?->email,
                    'payload' => $payload,
                ]);

                ProcessWebhookJob::dispatch($log->id)->onQueue('webhooks');
                $synced++;
            } catch (\Throwable $e) {
                $errors[] = "send #{$send->id}: {$e->getMessage()}";
                Log::warning("CampaignStatsSyncService: send #{$send->id} — {$e->getMessage()}");
            } finally {
                $processed++;
            }
        }

        return [
            'ok' => true,
            'total' => $total,
            'synced' => $synced,
            'processed' => $processed,
            'errors' => $errors,
            'window' => 'manual-chunk',
        ];
    }

    private function eligibleSendsQuery(
        ?int $campaignId,
        ?int $hours,
        int $days,
        int $limit,
        bool $applyWindow,
    ): Builder {
        $query = CampaignSend::query()
            ->whereIn('status', ['sent', 'pending', 'delivered', 'opened'])
            ->whereNotNull('elastic_email_transaction_id')
            ->orderBy('sent_at', 'asc');

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        if ($applyWindow) {
            $cutoff = $hours ? now()->subHours($hours) : now()->subDays($days);
            $query->where('sent_at', '>=', $cutoff);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query;
    }

    private function buildEmailsApi(string $apiKey): EmailsApi
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('X-ElasticEmail-ApiKey', $apiKey);

        return new EmailsApi(new Client(), $config);
    }

    private function buildEventsApi(string $apiKey): EventsApi
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('X-ElasticEmail-ApiKey', $apiKey);

        return new EventsApi(new Client(), $config);
    }

    private function normaliseStatusFromJob(object $result): ?string
    {
        return match (true) {
            (int) ($result->getClickedCount() ?? 0) > 0 => 'clicked',
            (int) ($result->getOpenedCount() ?? 0) > 0 => 'opened',
            (int) ($result->getDeliveredCount() ?? 0) > 0 => 'delivered',
            (int) ($result->getAbuseReportsCount() ?? 0) > 0 => 'abusereport',
            (int) ($result->getUnsubscribedCount() ?? 0) > 0 => 'unsubscribed',
            (int) ($result->getFailedCount() ?? 0) > 0 => 'failed',
            default => null,
        };
    }

    private function extractFailureReason(object $result): string
    {
        $failed = $result->getFailed() ?? [];

        if (empty($failed)) {
            return '';
        }

        $firstFailure = $failed[0];

        return method_exists($firstFailure, 'getError')
            ? (string) ($firstFailure->getError() ?? '')
            : '';
    }

    private function resolveBackfillEvent(
        EmailsApi $emailsApi,
        EventsApi $eventsApi,
        CampaignSend $send,
        object $statusResult,
        string $status
    ): array {
        $eventType = $this->statusToEventType($status);

        $messageIds = method_exists($statusResult, 'getMessageIds')
            ? (array) ($statusResult->getMessageIds() ?? [])
            : [];

        if (! empty($messageIds[0])) {
            $emailData = $emailsApi->emailsByMsgidViewGet($messageIds[0]);
            $timestamp = $this->timestampFromEmailData($emailData, $status);

            if ($timestamp) {
                return [$eventType, $timestamp];
            }
        }

        $event = $this->latestRelevantEvent($eventsApi, $send, $status);

        return [$eventType, $event?->getEventDate()];
    }

    private function statusToEventType(string $status): string
    {
        return match ($status) {
            'clicked' => 'Click',
            'opened' => 'Open',
            'delivered' => 'Delivered',
            'unsubscribed' => 'Unsubscribe',
            'abusereport' => 'Complaint',
            'failed' => 'Failed',
            default => ucfirst($status),
        };
    }

    private function timestampFromEmailData(?EmailData $emailData, string $status): ?\DateTimeInterface
    {
        $emailStatus = $emailData?->getStatus();

        if (! $emailStatus instanceof EmailStatus) {
            return null;
        }

        return match ($status) {
            'clicked' => $emailStatus->getDateClicked() ?: $emailStatus->getStatusChangeDate(),
            'opened' => $emailStatus->getDateOpened() ?: $emailStatus->getStatusChangeDate(),
            'delivered' => $emailStatus->getStatusChangeDate(),
            'failed', 'abusereport', 'unsubscribed' => $emailStatus->getStatusChangeDate(),
            default => null,
        };
    }

    private function latestRelevantEvent(EventsApi $eventsApi, CampaignSend $send, string $status): ?RecipientEvent
    {
        if (! in_array($status, ['clicked', 'opened', 'failed', 'abusereport', 'unsubscribed'], true)) {
            return null;
        }

        $from = $send->sent_at?->copy()->subDay();
        $events = $eventsApi->eventsByTransactionidGet(
            $send->elastic_email_transaction_id,
            $from,
            null,
            EventsOrderBy::DATE_DESCENDING,
            50,
            0,
        );

        foreach ($events as $event) {
            if (! $event instanceof RecipientEvent) {
                continue;
            }

            if ($this->eventMatchesStatus($event, $status)) {
                return $event;
            }
        }

        return null;
    }

    private function eventMatchesStatus(RecipientEvent $event, string $status): bool
    {
        return match ($status) {
            'clicked' => $event->getEventType() === EventType::CLICK,
            'opened' => $event->getEventType() === EventType::OPEN,
            'failed' => in_array($event->getEventType(), [EventType::FAILED_ATTEMPT, EventType::BOUNCE], true),
            'unsubscribed' => $event->getEventType() === EventType::UNSUBSCRIBE,
            'abusereport' => $event->getEventType() === EventType::COMPLAINT,
            default => false,
        };
    }
}
