<?php

namespace App\Console\Commands\Foundation;

use App\Models\FocalPointApplication;
use App\Models\FocalPointApplicationDraft;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

class ReportFocalPointApplicationHealth extends Command
{
    protected $signature = 'foundation:report-focal-point-application-health';

    protected $description = 'Write focal point application capacity and failure metrics to the application log.';

    public function handle(): int
    {
        $uploads = FocalPointApplicationDraft::query()
            ->selectRaw('cv_upload_status as status, count(*) as total')
            ->groupBy('cv_upload_status')
            ->pluck('total', 'status')
            ->all();
        $certificateUploads = FocalPointApplicationDraft::query()
            ->selectRaw('certificate_upload_status as status, count(*) as total')
            ->groupBy('certificate_upload_status')
            ->pluck('total', 'status')
            ->all();

        $metrics = [
            'submissions_last_minute' => FocalPointApplication::query()->where('created_at', '>=', now()->subMinute())->count(),
            'active_drafts' => FocalPointApplicationDraft::query()->where('expires_at', '>', now())->count(),
            'expired_drafts' => FocalPointApplicationDraft::query()->where('expires_at', '<=', now())->count(),
            'receipt_email_failures_last_hour' => FocalPointApplication::query()->where('confirmation_email_failed_at', '>=', now()->subHour())->count(),
            'cv_uploads' => $uploads,
            'certificate_uploads' => $certificateUploads,
            'staged_upload_bytes' => collect(Storage::disk('local')->allFiles('focal-point-drafts'))
                ->sum(fn (string $path): int => Storage::disk('local')->size($path)),
            'storage_available_bytes' => disk_free_space(storage_path()) ?: null,
        ];

        foreach (['focal-point-uploads', 'focal-point-mail'] as $queue) {
            try {
                $metrics["{$queue}_depth"] = Queue::size($queue);
            } catch (\Throwable $exception) {
                $metrics["{$queue}_depth"] = null;
                $metrics["{$queue}_error"] = $exception::class;
            }
        }

        Log::info('Focal point application health.', $metrics);
        $this->info(json_encode($metrics, JSON_THROW_ON_ERROR));

        return self::SUCCESS;
    }
}
