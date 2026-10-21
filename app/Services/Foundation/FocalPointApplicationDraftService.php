<?php

namespace App\Services\Foundation;

use App\Models\FocalPointApplicationDraft;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FocalPointApplicationDraftService
{
    private const DRAFT_LIFETIME_DAYS = 14;

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
                'expires_at' => now()->addDays(self::DRAFT_LIFETIME_DAYS),
            ]);
        }

        if ($step === 0 && Str::lower((string) $answers['email']) !== $draft->email) {
            throw ValidationException::withMessages(['email' => 'This saved application belongs to a different email address. Request a resume link or start a new application.']);
        }

        $previousIds = [
            'cv' => $draft?->cv_drive_id,
            'certificate' => $draft?->certificate_drive_id,
        ];
        $uploaded = [];
        try {
            if ($cv) {
                $uploaded['cv'] = $this->drive->upload($cv, 'cv');
            }
            if ($certificate) {
                $uploaded['certificate'] = $this->drive->upload($certificate, 'certificate');
            }

            $draft->fill([
                'answers' => [...($draft->answers ?? []), ...Arr::except($answers, ['website'])],
                'last_completed_step' => max((int) ($draft->last_completed_step ?? 0), $step),
                'expires_at' => now()->addDays(self::DRAFT_LIFETIME_DAYS),
            ]);
            foreach ($uploaded as $kind => $file) {
                $draft->setAttribute("{$kind}_drive_id", $file['id']);
                $draft->setAttribute("{$kind}_drive_url", $file['url']);
                $draft->setAttribute("{$kind}_filename", $file['filename']);
            }
            $draft->save();
        } catch (\Throwable $exception) {
            foreach ($uploaded as $file) {
                try {
                    $this->drive->delete($file['id']);
                } catch (\Throwable) {
                }
            }
            throw $exception;
        }

        foreach ($uploaded as $kind => $file) {
            $previousId = $previousIds[$kind];
            if ($previousId && $previousId !== $file['id']) {
                try {
                    $this->drive->delete($previousId);
                } catch (\Throwable) {
                }
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

    public function resume(string $uuid, string $token): ?FocalPointApplicationDraft
    {
        $draft = FocalPointApplicationDraft::query()
            ->where('uuid', $uuid)
            ->where('expires_at', '>', now())
            ->first();

        if (! $draft || ! $draft->resume_token_expires_at?->isFuture() || ! hash_equals((string) $draft->resume_token_hash, hash('sha256', $token))) {
            return null;
        }

        $draft->forceFill([
            'resume_token_hash' => null,
            'resume_token_expires_at' => null,
            'email_verified_at' => now(),
        ])->save();

        return $draft;
    }

    public function hasDocuments(FocalPointApplicationDraft $draft): bool
    {
        return filled($draft->cv_drive_id) && filled($draft->certificate_drive_id);
    }

    public function deleteAfterSubmission(FocalPointApplicationDraft $draft): void
    {
        $draft->delete();
    }
}
