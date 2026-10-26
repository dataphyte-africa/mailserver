<?php

namespace App\Services\Foundation;

use App\Jobs\Foundation\UploadFocalPointApplicationDocument;
use App\Models\FocalPointApplicationDraft;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FocalPointApplicationDraftService
{
    private const DRAFT_LIFETIME_HOURS = 72;

    public function __construct(private readonly GoogleDriveApplicationStorage $drive) {}

    /** @param array<string, mixed> $answers */
    public function save(?FocalPointApplicationDraft $draft, int $step, array $answers, ?UploadedFile $cv = null, ?UploadedFile $certificate = null): FocalPointApplicationDraft
    {
        if ($step === 0 && blank($answers['email'] ?? null)) {
            throw ValidationException::withMessages(['email' => 'Enter an email address before saving your progress.']);
        }

        if (! $draft && $step !== 0) {
            throw ValidationException::withMessages(['draft' => 'Save your personal information before continuing.']);
        }

        if (! $draft) {
            $draft = new FocalPointApplicationDraft([
                'uuid' => (string) Str::uuid(),
                'email' => Str::lower((string) $answers['email']),
                'answers' => [],
                'expires_at' => now()->addHours(self::DRAFT_LIFETIME_HOURS),
            ]);
        }

        if ($step === 0 && Str::lower((string) $answers['email']) !== $draft->email) {
            throw ValidationException::withMessages(['email' => 'This saved application belongs to a different email address. Request a resume link or start a new application.']);
        }

        $draft->fill([
            'answers' => [...($draft->answers ?? []), ...Arr::except($answers, ['website', 'cv', 'certificate'])],
            'last_completed_step' => max((int) ($draft->last_completed_step ?? 0), $step),
        ]);
        $draft->save();

        foreach (['cv' => $cv, 'certificate' => $certificate] as $kind => $file) {
            if ($file) {
                $this->stageDocument($draft, $kind, $file);
            }
        }

        return $draft->fresh();
    }

    public function fromSession(?string $uuid): ?FocalPointApplicationDraft
    {
        if (blank($uuid)) {
            return null;
        }

        return FocalPointApplicationDraft::query()
            ->where('uuid', $uuid)
            ->where('expires_at', '>', now())
            ->first();
    }

    public function createResumeToken(FocalPointApplicationDraft $draft): string
    {
        $token = Str::random(64);
        $draft->forceFill([
            'resume_token_hash' => hash('sha256', $token),
            'resume_token_expires_at' => now()->addMinutes(30),
        ])->save();

        return $token;
    }

    public function findResumable(string $uuid, string $token): ?FocalPointApplicationDraft
    {
        $draft = FocalPointApplicationDraft::query()
            ->where('uuid', $uuid)
            ->where('expires_at', '>', now())
            ->first();

        if (! $draft || ! $draft->resume_token_expires_at?->isFuture() || ! hash_equals((string) $draft->resume_token_hash, hash('sha256', $token))) {
            return null;
        }

        return $draft;
    }

    public function resume(string $uuid, string $token): ?FocalPointApplicationDraft
    {
        $draft = $this->findResumable($uuid, $token);
        if (! $draft) {
            return null;
        }

        $consumed = FocalPointApplicationDraft::query()
            ->whereKey($draft->id)
            ->where('resume_token_hash', hash('sha256', $token))
            ->where('resume_token_expires_at', '>', now())
            ->update([
                'resume_token_hash' => null,
                'resume_token_expires_at' => null,
                'email_verified_at' => now(),
                'updated_at' => now(),
            ]);
        if ($consumed !== 1) {
            return null;
        }

        return $draft->fresh();
    }

    public function hasDocuments(FocalPointApplicationDraft $draft): bool
    {
        return filled($draft->cv_drive_id) && filled($draft->certificate_drive_id)
            && $draft->cv_upload_status === 'ready'
            && $draft->certificate_upload_status === 'ready';
    }

    public function deleteAfterSubmission(FocalPointApplicationDraft $draft): void
    {
        $draft->delete();
    }

    private function stageDocument(FocalPointApplicationDraft $draft, string $kind, UploadedFile $file): void
    {
        $disk = Storage::disk('local');
        $extension = $file->extension() ?: 'upload';
        $path = $file->storeAs(
            "focal-point-drafts/{$draft->uuid}",
            "{$kind}-".Str::uuid().".{$extension}",
            'local',
        );
        if (! $path) {
            throw new \RuntimeException('We could not stage this document. Please try again.');
        }

        $pathColumn = "{$kind}_upload_path";
        $oldPath = $draft->{$pathColumn};
        $draft->forceFill([
            $pathColumn => $path,
            "{$kind}_upload_filename" => $file->getClientOriginalName(),
            "{$kind}_upload_status" => 'queued',
            "{$kind}_upload_error" => null,
            "{$kind}_upload_queued_at" => now(),
            "{$kind}_uploaded_at" => null,
        ])->save();
        if ($oldPath && $oldPath !== $path) {
            $disk->delete($oldPath);
        }

        UploadFocalPointApplicationDocument::dispatch($draft->uuid, $kind, $path)
            ->onQueue('focal_point_uploads');
    }
}
