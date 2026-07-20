<?php

namespace App\Console\Commands\Newsletter;

use App\Services\Newsletter\ApplicationSubmissionTrackingService;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Statamic\Contracts\Forms\Submission as StatamicSubmission;
use Statamic\Facades\Form;

class CleanupApplicationFormSubmissions extends Command
{
    protected $signature = 'newsletter:cleanup-application-form-submissions
        {--form= : Restrict cleanup to a specific application form handle}
        {--failed-only : Only target failed historical rows with no subscriber linkage}
        {--duplicates-only : Only target duplicate historical rows}
        {--dry-run : Report rows without deleting them}
        {--export-report : Write the targeted rows to a CSV report before deletion}';

    protected $description = 'Clean historical failed or duplicate Statamic submissions for application forms.';

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

        $dryRun = (bool) $this->option('dry-run');
        $includeFailed = ! $this->option('duplicates-only');
        $includeDuplicates = ! $this->option('failed-only');

        if (! $includeFailed && ! $includeDuplicates) {
            $this->error('Nothing to do. Remove conflicting filters and try again.');

            return self::FAILURE;
        }

        $submissions = $this->collectTargetSubmissions($tracking, $form);

        if ($submissions->isEmpty()) {
            $this->table(
                ['Scope', 'Mode', 'Targeted Rows', 'Deleted Rows'],
                [[
                    $form?->handle() ?? 'all application forms',
                    $this->modeLabel($includeFailed, $includeDuplicates),
                    '0',
                    '0',
                ]]
            );
            $this->info('Application-form submission cleanup completed.');

            return self::SUCCESS;
        }

        $targets = collect();

        if ($includeFailed) {
            $targets = $targets->merge($this->failedRows($submissions));
        }

        if ($includeDuplicates) {
            $duplicates = $this->duplicateRows($submissions);

            // Never delete the same row twice if it also matched the failed set.
            $targets = $targets->merge(
                $duplicates->filter(fn (array $row) => ! $targets->contains(fn (array $target) => $target['id'] === $row['id']))
            );
        }

        $targets = $targets
            ->sortBy([
                ['form', 'asc'],
                ['category', 'asc'],
                ['email', 'asc'],
                ['phone_number', 'asc'],
                ['date', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        if ($targets->isEmpty()) {
            $this->table(
                ['Scope', 'Mode', 'Targeted Rows', 'Deleted Rows'],
                [[
                    $form?->handle() ?? 'all application forms',
                    $this->modeLabel($includeFailed, $includeDuplicates),
                    '0',
                    '0',
                ]]
            );
            $this->info('No matching historical failed or duplicate submissions were found.');

            return self::SUCCESS;
        }

        $reportPath = null;

        if ($this->option('export-report')) {
            $reportPath = $this->writeReport($targets, $form?->handle());
            $this->line("Report written to: {$reportPath}");
        }

        $deleted = 0;

        if (! $dryRun) {
            foreach ($targets as $target) {
                /** @var StatamicSubmission|null $submission */
                $submission = $target['submission'];

                if (! $submission) {
                    continue;
                }

                $submission->delete();
                $deleted++;
            }
        }

        $this->table(
            ['Scope', 'Mode', 'Targeted Rows', 'Deleted Rows'],
            [[
                $form?->handle() ?? 'all application forms',
                $this->modeLabel($includeFailed, $includeDuplicates).($dryRun ? ' (dry-run)' : ''),
                (string) $targets->count(),
                (string) $deleted,
            ]]
        );

        $this->table(
            ['Category', 'Rows'],
            $targets
                ->groupBy('category')
                ->map(fn (Collection $rows, string $category) => [
                    'Category' => $category,
                    'Rows' => (string) $rows->count(),
                ])
                ->values()
                ->all()
        );

        $this->info('Application-form submission cleanup completed.');

        if ($dryRun) {
            $this->comment('Dry run only. Re-run without --dry-run to delete the targeted rows.');
        }

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

    private function failedRows(Collection $submissions): Collection
    {
        return $submissions
            ->filter(fn (StatamicSubmission $submission) => blank($submission->get('subscriber_id')) && blank($submission->get('subscription_status')))
            ->map(fn (StatamicSubmission $submission) => $this->serializeTarget($submission, 'failed'))
            ->values();
    }

    private function duplicateRows(Collection $submissions): Collection
    {
        return $submissions
            ->groupBy(fn (StatamicSubmission $submission) => $this->fingerprint($submission))
            ->filter(fn (Collection $group, string $fingerprint) => $fingerprint !== '' && $group->count() > 1)
            ->flatMap(function (Collection $group) {
                $ordered = $group
                    ->sort(function (StatamicSubmission $left, StatamicSubmission $right) {
                        $score = $this->submissionQualityScore($right) <=> $this->submissionQualityScore($left);

                        if ($score !== 0) {
                            return $score;
                        }

                        $dateScore = $this->submissionTimestamp($left) <=> $this->submissionTimestamp($right);

                        if ($dateScore !== 0) {
                            return $dateScore;
                        }

                        return strcmp((string) $left->id(), (string) $right->id());
                    })
                    ->values();

                return $ordered
                    ->slice(1)
                    ->map(fn (StatamicSubmission $submission) => $this->serializeTarget($submission, 'duplicate', [
                        'canonical_id' => (string) $ordered->first()?->id(),
                    ]));
            })
            ->values();
    }

    private function fingerprint(StatamicSubmission $submission): string
    {
        $email = $this->normalizeEmail($submission->get('email'));
        $phone = $this->normalizePhone($submission->get('phone_number'));

        if ($email === '' && $phone === '') {
            return '';
        }

        return implode('|', [
            (string) $submission->form()->handle(),
            $email,
            $phone,
        ]);
    }

    private function serializeTarget(StatamicSubmission $submission, string $category, array $extra = []): array
    {
        return array_merge([
            'id' => (string) $submission->id(),
            'form' => (string) $submission->form()->handle(),
            'category' => $category,
            'email' => $this->normalizeEmail($submission->get('email')),
            'phone_number' => $this->normalizePhone($submission->get('phone_number')),
            'full_name' => trim((string) $submission->get('full_name')),
            'subscription_status' => (string) ($submission->get('subscription_status') ?? ''),
            'subscriber_id' => (string) ($submission->get('subscriber_id') ?? ''),
            'confirmation_email_status' => (string) ($submission->get(ApplicationSubmissionTrackingService::STATUS_FIELD) ?? ''),
            'date' => $submission->date()?->toIso8601String() ?? '',
            'submission' => $submission,
        ], $extra);
    }

    private function submissionQualityScore(StatamicSubmission $submission): int
    {
        $score = 0;

        if (filled($submission->get('subscriber_id'))) {
            $score += 100;
        }

        if (filled($submission->get('subscription_status'))) {
            $score += 50;
        }

        if (filled($submission->get(ApplicationSubmissionTrackingService::STATUS_FIELD))) {
            $score += 25;
        }

        if (filled($submission->get(ApplicationSubmissionTrackingService::DELIVERED_AT_FIELD))) {
            $score += 20;
        }

        if (filled($submission->get(ApplicationSubmissionTrackingService::QUEUED_AT_FIELD))) {
            $score += 10;
        }

        return $score;
    }

    private function submissionTimestamp(StatamicSubmission $submission): int
    {
        $date = $submission->date();

        return $date instanceof CarbonInterface ? $date->timestamp : PHP_INT_MAX;
    }

    private function normalizeEmail(mixed $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function normalizePhone(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?: '';
    }

    private function modeLabel(bool $includeFailed, bool $includeDuplicates): string
    {
        return match (true) {
            $includeFailed && $includeDuplicates => 'failed + duplicates',
            $includeFailed => 'failed only',
            $includeDuplicates => 'duplicates only',
            default => 'none',
        };
    }

    private function writeReport(Collection $targets, ?string $formHandle): string
    {
        $timestamp = now()->format('Ymd-His');
        $scope = $formHandle ?: 'all-application-forms';
        $directory = storage_path('app/reports/newsletter/submission-cleanup');

        File::ensureDirectoryExists($directory);

        $path = "{$directory}/{$scope}-{$timestamp}.csv";
        $stream = fopen($path, 'wb');

        fputcsv($stream, [
            'submission_id',
            'form',
            'category',
            'email',
            'phone_number',
            'full_name',
            'subscription_status',
            'subscriber_id',
            'confirmation_email_status',
            'canonical_id',
            'date',
        ]);

        foreach ($targets as $target) {
            fputcsv($stream, [
                $target['id'],
                $target['form'],
                $target['category'],
                $target['email'],
                $target['phone_number'],
                $target['full_name'],
                $target['subscription_status'],
                $target['subscriber_id'],
                $target['confirmation_email_status'],
                $target['canonical_id'] ?? '',
                $target['date'],
            ]);
        }

        fclose($stream);

        return $path;
    }
}
