<?php

namespace App\Jobs\Foundation;

use App\Models\FocalPointApplicationDraft;
use App\Services\Foundation\GoogleDriveApplicationStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadFocalPointApplicationDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 90;

    /** @var array<int, int> */
    public array $backoff = [15, 60, 300];

    public function __construct(
        public readonly string $draftUuid,
        public readonly string $kind,
        public readonly string $path,
    ) {}

    public function handle(GoogleDriveApplicationStorage $drive): void
    {
        $draft = FocalPointApplicationDraft::query()
            ->where('uuid', $this->draftUuid)
            ->where('expires_at', '>', now())
            ->first();
        $pathColumn = "{$this->kind}_upload_path";
        $filenameColumn = "{$this->kind}_upload_filename";

        if (! $draft || ! in_array($this->kind, ['cv', 'certificate'], true) || $draft->{$pathColumn} !== $this->path) {
            Storage::disk('local')->delete($this->path);

            return;
        }

        if (! Storage::disk('local')->exists($this->path)) {
            $this->markFailed('The staged document could not be found. Please select it again.');

            return;
        }

        $statusColumn = "{$this->kind}_upload_status";
        $errorColumn = "{$this->kind}_upload_error";
        $driveIdColumn = "{$this->kind}_drive_id";
        $driveUrlColumn = "{$this->kind}_drive_url";
        $documentFilenameColumn = "{$this->kind}_filename";
        $previousDriveId = $draft->{$driveIdColumn};

        $draft->forceFill([$statusColumn => 'uploading', $errorColumn => null])->save();

        try {
            $file = new UploadedFile(
                Storage::disk('local')->path($this->path),
                $draft->{$filenameColumn},
                Storage::disk('local')->mimeType($this->path) ?: null,
                null,
                true,
            );
            $uploaded = $drive->upload($file, $this->kind);
        } catch (\Throwable $exception) {
            Log::warning('Focal point document upload attempt failed.', [
                'draft_uuid' => $this->draftUuid,
                'document' => $this->kind,
                'exception' => $exception::class,
            ]);

            throw $exception;
        }

        $updated = FocalPointApplicationDraft::query()
            ->whereKey($draft->id)
            ->where($pathColumn, $this->path)
            ->update([
                $driveIdColumn => $uploaded['id'],
                $driveUrlColumn => $uploaded['url'],
                $documentFilenameColumn => $uploaded['filename'],
                $pathColumn => null,
                $filenameColumn => null,
                $statusColumn => 'ready',
                $errorColumn => null,
                "{$this->kind}_uploaded_at" => now(),
                'updated_at' => now(),
            ]);

        Storage::disk('local')->delete($this->path);
        if ($updated !== 1) {
            $drive->delete($uploaded['id']);

            return;
        }

        if ($previousDriveId && $previousDriveId !== $uploaded['id']) {
            $drive->delete($previousDriveId);
        }

        Log::info('Focal point document upload completed.', [
            'draft_uuid' => $this->draftUuid,
            'document' => $this->kind,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->markFailed('We could not upload this document. Please try again.');
        Log::error('Focal point document upload failed permanently.', [
            'draft_uuid' => $this->draftUuid,
            'document' => $this->kind,
            'exception' => $exception::class,
        ]);
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return ['focal-point', 'document-upload', "draft:{$this->draftUuid}", "document:{$this->kind}"];
    }

    private function markFailed(string $message): void
    {
        if (! in_array($this->kind, ['cv', 'certificate'], true)) {
            return;
        }

        $pathColumn = "{$this->kind}_upload_path";
        FocalPointApplicationDraft::query()
            ->where('uuid', $this->draftUuid)
            ->where($pathColumn, $this->path)
            ->update([
                "{$this->kind}_upload_status" => 'failed',
                "{$this->kind}_upload_error" => $message,
                'updated_at' => now(),
            ]);
    }
}
