<?php

namespace App\Console\Commands\Newsletter;

use App\Jobs\Newsletter\ProcessWebhookJob;
use App\Models\WebhookLog;
use App\Services\Newsletter\ApplicationSubmissionTrackingService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Statamic\Facades\Form;
use Statamic\Contracts\Forms\Submission as StatamicSubmission;

class BackfillApplicationFormDeliveryTracking extends Command
{
    protected $signature = 'newsletter:backfill-application-form-delivery
        {--form= : Restrict backfill to a specific application form handle}
        {--only-untracked : Skip submissions that already have a last tracked event}';

    protected $description = 'Backfill application-form confirmation email delivery tracking from Elastic Email webhook logs.';

    public function handle(ApplicationSubmissionTrackingService $tracking): int
    {
        $formOption = $this->option('form');
        $form = null;

        if (is_string($formOption) && $formOption !== '') {
            $form = Form::find($formOption);

            if (! $form || ! $tracking->isApplicationForm($form)) {
                $this->error("Application form [{$formOption}] was not found.");

                return self::FAILURE;
            }
        }

        $submissions = $this->collectTargetSubmissions($tracking, $form);

        if ($this->option('only-untracked')) {
            $submissions = $submissions
                ->filter(fn (StatamicSubmission $submission) => blank($submission->get(ApplicationSubmissionTrackingService::LAST_EVENT_FIELD)))
                ->values();
        }

        if ($submissions->isEmpty()) {
            $this->table(
                ['Processed Logs', 'Updated Submissions', 'Skipped Logs'],
                [['0', '0', '0']]
            );
            $this->info('Application-form delivery tracking backfill completed.');

            return self::SUCCESS;
        }

        $submissionIds = $submissions->keyBy(fn (StatamicSubmission $submission) => (string) $submission->id());
        $submissionEmails = $submissions
            ->mapWithKeys(function (StatamicSubmission $submission) {
                $email = strtolower(trim((string) $submission->get('email')));

                return $email !== '' ? [$email => true] : [];
            })
            ->keys()
            ->values();
        $submissionTransactions = $submissions
            ->map(fn (StatamicSubmission $submission) => trim((string) $submission->get(ApplicationSubmissionTrackingService::TRANSACTION_ID_FIELD)))
            ->filter()
            ->unique()
            ->values();
        $submissionsByEmail = $submissions
            ->filter(fn (StatamicSubmission $submission) => filled($submission->get('email')))
            ->groupBy(fn (StatamicSubmission $submission) => strtolower(trim((string) $submission->get('email'))))
            ->map(fn (Collection $items) => $items->sortByDesc(fn (StatamicSubmission $submission) => $submission->date()?->timestamp ?? 0)->values());
        $minimumSubmissionDate = $submissions
            ->map(fn (StatamicSubmission $submission) => $submission->date())
            ->filter()
            ->sort()
            ->first();

        $processed = 0;
        $updated = 0;
        $skipped = 0;

        $query = WebhookLog::query();

        if ($minimumSubmissionDate instanceof CarbonInterface) {
            $query->where('created_at', '>=', $minimumSubmissionDate->copy()->subDay());
        }

        $query = $query->where(function ($query) use ($submissionEmails, $submissionTransactions) {
            foreach ($submissionEmails->chunk(500) as $emailChunk) {
                $query->orWhereIn('to_email', $emailChunk->all());
            }

            foreach ($submissionTransactions->chunk(500) as $transactionChunk) {
                $query->orWhereIn('transaction_id', $transactionChunk->all());
            }
        });

        $query
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($tracking, $form, $submissionIds, $submissionsByEmail, &$processed, &$updated, &$skipped) {
                foreach ($logs as $log) {
                    $processed++;

                    $event = ProcessWebhookJob::normaliseEventType($log->event_type);

                    if (! $event) {
                        $skipped++;
                        continue;
                    }

                    $submission = $this->resolveBackfillSubmission(
                        $tracking,
                        $log,
                        $form,
                        $submissionIds,
                        $submissionsByEmail,
                    );

                    if (! $submission) {
                        $skipped++;
                        continue;
                    }

                    $tracking->applyWebhookEvent($submission, $log, $event, markBackfilled: true);
                    $updated++;
                }
            });

        $this->table(
            ['Processed Logs', 'Updated Submissions', 'Skipped Logs'],
            [[(string) $processed, (string) $updated, (string) $skipped]]
        );

        $this->info('Application-form delivery tracking backfill completed.');

        return self::SUCCESS;
    }

    private function collectTargetSubmissions(ApplicationSubmissionTrackingService $tracking, $form): Collection
    {
        if ($form) {
            return $form->submissions()->values();
        }

        return Form::all()
            ->filter(fn ($candidate) => $tracking->isApplicationForm($candidate))
            ->flatMap(fn ($candidate) => $candidate->submissions())
            ->values();
    }

    private function resolveBackfillSubmission(
        ApplicationSubmissionTrackingService $tracking,
        WebhookLog $log,
        $form,
        Collection $submissionIds,
        Collection $submissionsByEmail,
    ): ?StatamicSubmission {
        $payload = $log->payload ?? [];
        $submissionId = (string) ($payload['submission_id'] ?? '');

        if ($submissionId !== '' && $submissionIds->has($submissionId)) {
            return $submissionIds->get($submissionId);
        }

        if (filled($log->transaction_id)) {
            $resolved = $tracking->resolveSubmission($log, $form);

            if ($resolved) {
                return $resolved;
            }
        }

        $email = strtolower(trim((string) $log->to_email));

        if ($email === '' || ! $submissionsByEmail->has($email)) {
            return null;
        }

        $candidateTime = $this->logTimestamp($log);

        return $submissionsByEmail->get($email)
            ->first(function (StatamicSubmission $submission) use ($candidateTime) {
                $submissionDate = $submission->date();

                if (! $submissionDate) {
                    return false;
                }

                if (! $candidateTime) {
                    return true;
                }

                return $submissionDate->lessThanOrEqualTo($candidateTime);
            });
    }

    private function logTimestamp(WebhookLog $log): ?CarbonInterface
    {
        foreach (['date', 'Date', 'timestamp', 'Timestamp', 'eventdate', 'EventDate'] as $key) {
            $value = data_get($log->payload ?? [], $key);

            if (filled($value)) {
                try {
                    return Carbon::parse((string) $value);
                } catch (\Throwable) {
                    // Fall back to created_at below.
                }
            }
        }

        return $log->created_at;
    }
}
