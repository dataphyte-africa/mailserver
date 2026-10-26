<?php

namespace App\Http\Controllers\CP\Forms;

use App\Models\FocalPointApplication;
use Illuminate\Contracts\View\View;
use Statamic\Facades\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FocalPointApplicationController
{
    public function index(): View
    {
        $this->assertSuper();

        return view('foundation.focal-points.cp.index', [
            'applications' => FocalPointApplication::query()->latest()->paginate(25),
        ]);
    }

    public function show(string $applicationId): View
    {
        $this->assertSuper();
        $application = FocalPointApplication::query()->findOrFail($applicationId);

        return view('foundation.focal-points.cp.show', compact('application'));
    }

    public function export(): StreamedResponse
    {
        $this->assertSuper();

        $headers = [
            'application_id', 'confirmation_email_status', 'confirmation_email_delivered_at',
            'confirmation_email_opened_at', 'confirmation_email_failed_at',
            'submitted_at', 'tor_version', 'tor_accepted_at',
            'full_name', 'primary_phone', 'other_phone', 'email', 'gender', 'age',
            'has_disability', 'education', 'linkedin_url', 'state', 'lga', 'ward',
            'residential_address', 'resident_in_state', 'residence_length', 'party_member',
            'candidate_2027', 'observed_before', 'observation_experience',
            'coordinated_before', 'coordination_experience', 'available_for_training',
            'recruitment_plan', 'polling_unit_scenario', 'tools', 'tools_other', 'cv_url', 'certificate_url',
        ];

        return response()->streamDownload(function () use ($headers): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, $headers);
            FocalPointApplication::query()->orderBy('id')->chunkById(200, function ($applications) use ($stream, $headers): void {
                foreach ($applications as $application) {
                    $row = [];
                    foreach ($headers as $header) {
                        $value = match ($header) {
                            'application_id' => $application->uuid,
                            'confirmation_email_status' => $application->confirmation_email_status,
                            'confirmation_email_delivered_at' => $application->confirmation_email_delivered_at?->toIso8601String(),
                            'confirmation_email_opened_at' => $application->confirmation_email_opened_at?->toIso8601String(),
                            'confirmation_email_failed_at' => $application->confirmation_email_failed_at?->toIso8601String(),
                            'submitted_at' => $application->created_at?->toIso8601String(),
                            'tor_version' => $application->tor_version,
                            'tor_accepted_at' => $application->tor_accepted_at?->toIso8601String(),
                            'cv_url' => $application->cv_drive_url,
                            'certificate_url' => $application->certificate_drive_url,
                            default => ($application->answers ?? [])[$header] ?? '',
                        };
                        $value = is_array($value) ? implode(', ', $value) : (string) $value;
                        $row[] = preg_match('/^[\s]*[=+\-@]/u', $value) ? "'{$value}" : $value;
                    }
                    fputcsv($stream, $row);
                }
            });
            fclose($stream);
        }, '2027-state-focal-point-applications.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function assertSuper(): void
    {
        abort_unless(User::current()?->isSuper(), 403);
    }
}
