<?php

namespace App\Console\Commands\Newsletter;

use App\Jobs\Newsletter\ProcessWebhookJob;
use App\Models\WebhookLog;
use App\Services\Newsletter\ApplicationSubmissionTrackingService;
use Illuminate\Console\Command;
use Statamic\Facades\Form;

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

        $processed = 0;
        $updated = 0;
        $skipped = 0;

        WebhookLog::query()
            ->orderBy('id')
            ->chunkById(200, function ($logs) use ($tracking, $form, &$processed, &$updated, &$skipped) {
                foreach ($logs as $log) {
                    $processed++;

                    $event = ProcessWebhookJob::normaliseEventType($log->event_type);

                    if (! $event) {
                        $skipped++;
                        continue;
                    }

                    $submission = $tracking->resolveSubmission($log, $form);

                    if (! $submission) {
                        $skipped++;
                        continue;
                    }

                    if ($this->option('only-untracked') && filled($submission->get(ApplicationSubmissionTrackingService::LAST_EVENT_FIELD))) {
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
}
