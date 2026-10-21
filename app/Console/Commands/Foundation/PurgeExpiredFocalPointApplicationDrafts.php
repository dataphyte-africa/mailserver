<?php

namespace App\Console\Commands\Foundation;

use App\Models\FocalPointApplicationDraft;
use App\Services\Foundation\GoogleDriveApplicationStorage;
use Illuminate\Console\Command;

class PurgeExpiredFocalPointApplicationDrafts extends Command
{
    protected $signature = 'foundation:purge-expired-focal-point-drafts {--dry-run}';

    protected $description = 'Delete expired State Focal Point drafts and their private documents.';

    public function handle(GoogleDriveApplicationStorage $drive): int
    {
        $query = FocalPointApplicationDraft::query()->where('expires_at', '<=', now());
        $count = $query->count();
        if ($count === 0) {
            $this->info('No expired focal-point drafts found.');

            return self::SUCCESS;
        }
        if ($this->option('dry-run')) {
            $this->info("{$count} expired focal-point draft(s) would be deleted.");

            return self::SUCCESS;
        }
        if (! $drive->configured()) {
            $this->error('Google Drive storage is not configured; expired draft documents were not deleted.');

            return self::FAILURE;
        }

        $deleted = 0;
        $query->chunkById(100, function ($drafts) use ($drive, &$deleted): void {
            foreach ($drafts as $draft) {
                foreach ([$draft->cv_drive_id, $draft->certificate_drive_id] as $fileId) {
                    if ($fileId) {
                        $drive->delete($fileId);
                    }
                }
                $draft->delete();
                $deleted++;
            }
        });

        $this->info("Deleted {$deleted} expired focal-point draft(s).");

        return self::SUCCESS;
    }
}
