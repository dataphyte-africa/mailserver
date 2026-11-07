<?php

namespace App\Services\Foundation;

use App\Jobs\Newsletter\ProcessWebhookJob;
use App\Models\FocalPointApplication;
use App\Models\WebhookLog;
use Carbon\Carbon;
use ElasticEmail\Api\EventsApi;
use ElasticEmail\Configuration;
use ElasticEmail\Model\EventType;
use ElasticEmail\Model\EventsOrderBy;
use GuzzleHttp\Client;

class FocalPointApplicationEmailBackfillService
{
    private const RECEIPT_SUBJECT = 'state coordinator application received';
    private const PAGE_SIZE = 1000;
    private const MAX_PAGES = 50;

    /**
     * Reconcile pending receipts against provider events without guessing delivery status.
     */
    public function sync(): array
    {
        $apiKey = config('mail.mailers.elasticemail.key');

        if (blank($apiKey)) {
            return ['ok' => false, 'error' => 'Elastic Email is not configured.'];
        }

        $applications = FocalPointApplication::query()
            ->where('confirmation_email_status', 'pending')
            ->get()
            ->keyBy(fn (FocalPointApplication $application) => strtolower($application->email));

        if ($applications->isEmpty()) {
            return ['ok' => true, 'updated' => 0, 'scanned' => 0, 'complete' => true];
        }

        $from = $applications->min('created_at')->copy()->subMinutes(5);
        $events = $this->matchingLatestEvents($applications->all(), $from);
        $updated = 0;

        foreach ($events as [$application, $event]) {
            $log = WebhookLog::create([
                'event_type' => $event->getEventType(),
                'transaction_id' => $event->getTransactionId(),
                'to_email' => strtolower((string) $event->getTo()),
                'payload' => [
                    'lifecycle_email' => 'focal_point_application_receipt',
                    'focal_point_application_id' => $application->uuid,
                    'subject' => 'State Coordinator application received',
                    'date' => $event->getEventDate()->format(DATE_ATOM),
                    '_source' => 'elastic_email_api_backfill',
                ],
            ]);

            ProcessWebhookJob::dispatchSync($log->id);
            $updated++;
        }

        return [
            'ok' => true,
            'updated' => $updated,
            'scanned' => $this->scanned,
            'complete' => $this->complete,
        ];
    }

    private int $scanned = 0;
    private bool $complete = true;

    /**
     * @param array<string, FocalPointApplication> $applications
     * @return array<string, array{FocalPointApplication, object}>
     */
    private function matchingLatestEvents(array $applications, Carbon $from): array
    {
        $api = $this->eventsApi();
        $matches = [];

        for ($offset = 0; $offset < self::PAGE_SIZE * self::MAX_PAGES; $offset += self::PAGE_SIZE) {
            $page = $api->eventsGet(
                [EventType::OPEN, EventType::CLICK, EventType::FAILED_ATTEMPT, EventType::BOUNCE],
                $from,
                now(),
                EventsOrderBy::DATE_DESCENDING,
                self::PAGE_SIZE,
                $offset,
            );

            $count = count($page);
            $this->scanned += $count;

            foreach ($page as $event) {
                $email = strtolower(trim((string) $event->getTo()));
                $application = $applications[$email] ?? null;

                if (! $application || isset($matches[$email]) || ! $event->getEventDate()) {
                    continue;
                }

                if (strtolower(trim((string) $event->getSubject())) !== self::RECEIPT_SUBJECT
                    || Carbon::instance($event->getEventDate())->lt($application->created_at)) {
                    continue;
                }

                // Results are descending, so retain the newest event per applicant.
                $matches[$email] = [$application, $event];
            }

            if ($count < self::PAGE_SIZE) {
                return $matches;
            }
        }

        $this->complete = false;

        return $matches;
    }

    private function eventsApi(): EventsApi
    {
        $configuration = Configuration::getDefaultConfiguration()
            ->setApiKey('X-ElasticEmail-ApiKey', config('mail.mailers.elasticemail.key'));

        return new EventsApi(new Client(), $configuration);
    }
}
