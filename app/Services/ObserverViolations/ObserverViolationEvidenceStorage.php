<?php

namespace App\Services\ObserverViolations;

use App\Models\ObserverViolationEvidence;
use App\Models\ObserverViolationReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ObserverViolationEvidenceStorage
{
    public function store(
        ObserverViolationReport $report,
        ObserverViolationEvidence $evidence,
        UploadedFile $file,
    ): ObserverViolationEvidence {
        $disk = (string) config('observer_violations.evidence_disk', 'local');
        $extension = $file->guessExtension() ?: $file->extension() ?: 'bin';
        $path = sprintf(
            'observer-violations/reports/%s/%s.%s',
            $report->uuid,
            $evidence->uuid,
            Str::lower($extension)
        );

        $stored = Storage::disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path)
        );

        if ($stored === false) {
            throw new \RuntimeException('Evidence upload could not be stored on the configured disk.');
        }

        $evidence->forceFill([
            'storage_disk' => $disk,
            'object_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'checksum' => is_file($file->getRealPath() ?: '') ? hash_file('sha256', $file->getRealPath()) : null,
            'upload_status' => 'stored',
            'metadata' => [
                'client_extension' => $file->clientExtension(),
                'detected_extension' => $extension,
            ],
            'failure_reason' => null,
        ])->save();

        return $evidence;
    }
}
