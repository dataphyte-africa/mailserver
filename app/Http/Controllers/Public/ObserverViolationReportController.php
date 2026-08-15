<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\ObserverViolation\StoreObserverViolationReportRequest;
use App\Services\ObserverViolations\ObserverViolationReportService;

class ObserverViolationReportController extends Controller
{
    public function __construct(
        private readonly ObserverViolationReportService $reports,
    ) {}

    public function store(StoreObserverViolationReportRequest $request)
    {
        $report = $this->reports->submit(
            $request->validated(),
            $request->file('evidence_files', [])
        );

        return response()->json([
            'success' => true,
            'report_id' => $report->uuid,
            'status' => $report->status,
            'evidence' => $report->evidence->map(fn ($item) => [
                'id' => $item->uuid,
                'original_filename' => $item->original_filename,
                'mime_type' => $item->mime_type,
                'file_size' => $item->file_size,
                'upload_status' => $item->upload_status,
            ])->values()->all(),
        ], 201);
    }
}
