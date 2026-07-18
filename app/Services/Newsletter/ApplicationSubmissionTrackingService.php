<?php

namespace App\Services\Newsletter;

use App\Models\WebhookLog;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Statamic\Contracts\Forms\Form as StatamicForm;
use Statamic\Contracts\Forms\Submission as StatamicSubmission;
use Statamic\Facades\Form;

class ApplicationSubmissionTrackingService
{
    public const STATUS_FIELD = 'confirmation_email_status';
    public const TRANSACTION_ID_FIELD = 'confirmation_email_transaction_id';
    public const QUEUED_AT_FIELD = 'confirmation_email_queued_at';
    public const DELIVERED_AT_FIELD = 'confirmation_email_delivered_at';
    public const OPENED_AT_FIELD = 'confirmation_email_opened_at';
    public const CLICKED_AT_FIELD = 'confirmation_email_clicked_at';
    public const BOUNCED_AT_FIELD = 'confirmation_email_bounced_at';
    public const FAILED_AT_FIELD = 'confirmation_email_failed_at';
    public const COMPLAINED_AT_FIELD = 'confirmation_email_complained_at';
    public const UNSUBSCRIBED_AT_FIELD = 'confirmation_email_unsubscribed_at';
    public const LAST_EVENT_FIELD = 'confirmation_email_last_event';
    public const LAST_EVENT_AT_FIELD = 'confirmation_email_last_event_at';
    public const BOUNCE_REASON_FIELD = 'confirmation_email_bounce_reason';
    public const BACKFILLED_AT_FIELD = 'confirmation_email_backfilled_at';

    public function trackingFieldDefinitions(): array
    {
        return [
            $this->readOnlyTextField(self::STATUS_FIELD, 'Confirmation Email Status', listable: true),
            $this->readOnlyTextField(self::TRANSACTION_ID_FIELD, 'Confirmation Email Transaction ID'),
            $this->readOnlyTextField(self::QUEUED_AT_FIELD, 'Confirmation Email Queued At'),
            $this->readOnlyTextField(self::DELIVERED_AT_FIELD, 'Confirmation Email Delivered At', listable: true),
            $this->readOnlyTextField(self::OPENED_AT_FIELD, 'Confirmation Email Opened At'),
            $this->readOnlyTextField(self::CLICKED_AT_FIELD, 'Confirmation Email Clicked At'),
            $this->readOnlyTextField(self::BOUNCED_AT_FIELD, 'Confirmation Email Bounced At', listable: true),
            $this->readOnlyTextField(self::FAILED_AT_FIELD, 'Confirmation Email Failed At', listable: true),
            $this->readOnlyTextField(self::COMPLAINED_AT_FIELD, 'Confirmation Email Complained At'),
            $this->readOnlyTextField(self::UNSUBSCRIBED_AT_FIELD, 'Confirmation Email Unsubscribed At'),
            $this->readOnlyTextField(self::LAST_EVENT_FIELD, 'Confirmation Email Last Event', listable: true),
            $this->readOnlyTextField(self::LAST_EVENT_AT_FIELD, 'Confirmation Email Last Event At'),
            $this->readOnlyTextareaField(self::BOUNCE_REASON_FIELD, 'Confirmation Email Bounce Reason', listable: true),
            $this->readOnlyTextField(self::BACKFILLED_AT_FIELD, 'Confirmation Email Backfilled At'),
        ];
    }

    public function queuedAttributes(?CarbonInterface $queuedAt = null): array
    {
        return [
            self::STATUS_FIELD => 'queued',
            self::QUEUED_AT_FIELD => ($queuedAt ?? now())->toIso8601String(),
        ];
    }

    public function mailHeaders(StatamicSubmission $submission, StatamicForm $form): array
    {
        return [
            'X-Form-Submission-Id' => (string) $submission->id(),
            'X-Form-Handle' => $form->handle(),
            'X-Submission-Mode' => 'application',
        ];
    }

    public function applyWebhookEvent(StatamicSubmission $submission, WebhookLog $log, string $event, bool $markBackfilled = false): array
    {
        $eventAt = $this->eventDate($log)?->toIso8601String() ?? now()->toIso8601String();

        $attributes = [
            self::LAST_EVENT_FIELD => $event,
            self::LAST_EVENT_AT_FIELD => $eventAt,
        ];

        if (filled($log->transaction_id)) {
            $attributes[self::TRANSACTION_ID_FIELD] = $log->transaction_id;
        }

        if ($markBackfilled) {
            $attributes[self::BACKFILLED_AT_FIELD] = now()->toIso8601String();
        }

        match ($event) {
            'delivered' => $attributes += [
                self::STATUS_FIELD => 'delivered',
                self::DELIVERED_AT_FIELD => $eventAt,
                self::BOUNCE_REASON_FIELD => null,
                self::FAILED_AT_FIELD => null,
                self::BOUNCED_AT_FIELD => null,
            ],
            'opened' => $attributes += [
                self::STATUS_FIELD => 'opened',
                self::OPENED_AT_FIELD => $eventAt,
                self::DELIVERED_AT_FIELD => $submission->get(self::DELIVERED_AT_FIELD) ?: $eventAt,
                self::BOUNCE_REASON_FIELD => null,
                self::FAILED_AT_FIELD => null,
                self::BOUNCED_AT_FIELD => null,
            ],
            'clicked' => $attributes += [
                self::STATUS_FIELD => 'clicked',
                self::CLICKED_AT_FIELD => $eventAt,
                self::OPENED_AT_FIELD => $submission->get(self::OPENED_AT_FIELD) ?: $eventAt,
                self::DELIVERED_AT_FIELD => $submission->get(self::DELIVERED_AT_FIELD) ?: $eventAt,
                self::BOUNCE_REASON_FIELD => null,
                self::FAILED_AT_FIELD => null,
                self::BOUNCED_AT_FIELD => null,
            ],
            'bounced' => $attributes += [
                self::STATUS_FIELD => 'bounced',
                self::BOUNCED_AT_FIELD => $eventAt,
                self::BOUNCE_REASON_FIELD => $this->bounceReason($log),
            ],
            'failed' => $attributes += [
                self::STATUS_FIELD => 'failed',
                self::FAILED_AT_FIELD => $eventAt,
                self::BOUNCE_REASON_FIELD => $this->bounceReason($log),
            ],
            'complained' => $attributes += [
                self::STATUS_FIELD => 'complained',
                self::COMPLAINED_AT_FIELD => $eventAt,
            ],
            'unsubscribed' => $attributes += [
                self::STATUS_FIELD => 'unsubscribed',
                self::UNSUBSCRIBED_AT_FIELD => $eventAt,
            ],
            default => $attributes += [
                self::STATUS_FIELD => $event,
            ],
        };

        $submission->data(array_merge($submission->data()->all(), $attributes))->save();

        return $attributes;
    }

    public function resolveSubmission(WebhookLog $log, ?StatamicForm $form = null): ?StatamicSubmission
    {
        $payload = $log->payload ?? [];
        $submissionId = $this->extractField($payload, ['submission_id']);
        $formHandle = $form?->handle() ?: $this->extractField($payload, ['form_handle']);

        if ($submissionId && $formHandle) {
            $resolvedForm = Form::find($formHandle);

            if ($resolvedForm && $this->isApplicationForm($resolvedForm)) {
                $submission = $resolvedForm->submission($submissionId);

                if ($submission) {
                    return $submission;
                }
            }
        }

        if ($submissionId) {
            foreach ($this->applicationForms() as $candidateForm) {
                $submission = $candidateForm->submission($submissionId);

                if ($submission) {
                    return $submission;
                }
            }
        }

        if ($log->transaction_id) {
            $match = $this->findLatestSubmission(fn (StatamicSubmission $submission) => $submission->get(self::TRANSACTION_ID_FIELD) === $log->transaction_id);

            if ($match) {
                return $match;
            }
        }

        if ($log->to_email) {
            return $this->findLatestSubmission(function (StatamicSubmission $submission) use ($log, $formHandle) {
                if (strtolower((string) $submission->get('email')) !== strtolower((string) $log->to_email)) {
                    return false;
                }

                if ($formHandle && $submission->form()?->handle() !== $formHandle) {
                    return false;
                }

                return true;
            });
        }

        return null;
    }

    public function isApplicationForm(StatamicForm $form): bool
    {
        return (string) $form->get('newsletter_submission_mode') === 'application';
    }

    private function applicationForms(): Collection
    {
        return Form::all()->filter(fn (StatamicForm $form) => $this->isApplicationForm($form))->values();
    }

    private function findLatestSubmission(callable $predicate): ?StatamicSubmission
    {
        $match = null;
        $latestTimestamp = null;

        foreach ($this->applicationForms() as $form) {
            foreach ($form->submissions() as $submission) {
                if (! $predicate($submission)) {
                    continue;
                }

                $timestamp = $submission->date()?->getTimestamp() ?? 0;

                if ($match === null || $timestamp >= $latestTimestamp) {
                    $match = $submission;
                    $latestTimestamp = $timestamp;
                }
            }
        }

        return $match;
    }

    private function eventDate(WebhookLog $log): ?CarbonInterface
    {
        $raw = $this->extractField($log->payload ?? [], ['date', 'Date', 'timestamp', 'Timestamp', 'eventdate', 'EventDate']);

        return $raw ? Carbon::parse($raw) : null;
    }

    private function bounceReason(WebhookLog $log): ?string
    {
        return $this->extractField($log->payload ?? [], ['bounceerror', 'BounceError', 'bounce_error', 'error', 'Error', 'message']);
    }

    private function extractField(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($payload, $key);

            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function readOnlyTextField(string $handle, string $display, bool $listable = false): array
    {
        return [
            'handle' => $handle,
            'field' => [
                'type' => 'text',
                'display' => $display,
                'read_only' => true,
                'listable' => $listable,
                'visibility' => 'read_only',
            ],
        ];
    }

    private function readOnlyTextareaField(string $handle, string $display, bool $listable = false): array
    {
        return [
            'handle' => $handle,
            'field' => [
                'type' => 'textarea',
                'display' => $display,
                'read_only' => true,
                'listable' => $listable,
                'visibility' => 'read_only',
            ],
        ];
    }
}
