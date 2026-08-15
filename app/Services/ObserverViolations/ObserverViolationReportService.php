<?php

namespace App\Services\ObserverViolations;

use App\Models\ObserverViolationEvidence;
use App\Models\ObserverViolationReport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ObserverViolationReportService
{
    public function __construct(
        private readonly ObserverViolationEvidenceStorage $storage,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $evidenceFiles
     */
    public function submit(array $payload, array $evidenceFiles = []): ObserverViolationReport
    {
        $reportAttributes = Arr::except($payload, ['evidence_files']);

        [$report, $evidenceRecords] = DB::transaction(function () use ($reportAttributes, $evidenceFiles): array {
            $report = ObserverViolationReport::query()->create(array_merge(
                $reportAttributes,
                [
                    'uuid' => (string) Str::uuid(),
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]
            ));

            $evidenceRecords = collect($evidenceFiles)->map(
                fn (UploadedFile $file) => $report->evidence()->create([
                    'uuid' => (string) Str::uuid(),
                    'evidence_kind' => 'file',
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'upload_status' => 'pending',
                ])
            );

            return [$report, $evidenceRecords];
        });

        $evidenceRecords->each(function (ObserverViolationEvidence $evidence, int $index) use ($report, $evidenceFiles): void {
            $file = $evidenceFiles[$index] ?? null;

            if (! $file instanceof UploadedFile) {
                return;
            }

            try {
                $this->storage->store($report, $evidence, $file);
            } catch (Throwable $exception) {
                $evidence->forceFill([
                    'storage_disk' => (string) config('observer_violations.evidence_disk', 'local'),
                    'upload_status' => 'failed',
                    'failure_reason' => Str::limit($exception->getMessage(), 1000, ''),
                ])->save();
            }
        });

        return $report->fresh('evidence');
    }
}
