<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\FocalPointApplicationReceivedMail;
use App\Services\Foundation\FocalPointApplicationContent;
use App\Services\Foundation\FocalPointApplicationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class FocalPointApplicationController extends Controller
{
    public function __construct(
        private readonly FocalPointApplicationService $applications,
        private readonly FocalPointApplicationContent $content,
    ) {}

    public function show(): View
    {
        $logoUrl = asset('assets/foundation/Dataphyte%20Foundation%20Logo.png');

        if (! $this->applications->isOpen() && ! session('application_received')) {
            return view('forms.public.closed', [
                'title' => 'State Focal Point applications',
                'brandName' => 'Dataphyte Foundation',
                'logoUrl' => $logoUrl,
                'closesAt' => $this->applications->closesAt(),
            ]);
        }

        return view('foundation.focal-points.application', [
            'logoUrl' => $logoUrl,
            'isOpen' => $this->applications->isOpen(),
            'isReady' => $this->applications->isReady(),
            'content' => $this->content,
            'torVersion' => $this->content->torVersion(),
            'torHtml' => $this->content->torHtml(),
            'closesAt' => $this->applications->closesAt(),
            'maxUploadMb' => (int) ceil(config('focal_point_applications.max_upload_kb', 10240) / 1024),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->applications->isOpen(), 410, 'Applications have closed.');
        abort_unless($this->applications->isReady(), 503, 'Applications are not accepting document uploads yet.');

        $validated = $request->validate($this->applications->rules());

        try {
            $application = $this->applications->submit(
                $validated,
                $request->file('cv'),
                $request->file('certificate'),
            );
        } catch (RuntimeException) {
            return back()->withInput($request->except(['cv', 'certificate']))
                ->withErrors(['documents' => 'We could not save your documents. Please try again.']);
        }

        try {
            Mail::to($application->email, $application->full_name)->queue(
                new FocalPointApplicationReceivedMail($application->full_name, $application->uuid)
            );
        } catch (\Throwable $exception) {
            $application->forceFill([
                'confirmation_email_status' => 'failed',
                'confirmation_email_failed_at' => now(),
            ])->save();
            report($exception);
        }

        return redirect()->route('focal-points.applications.show')
            ->with('application_received', $application->uuid);
    }

    public function states(): JsonResponse
    {
        return response()->json(['data' => $this->applications->states()]);
    }

    public function lgas(int $state): JsonResponse
    {
        return response()->json(['data' => $this->applications->lgas($state)]);
    }

    public function wards(int $state, int $lga): JsonResponse
    {
        return response()->json(['data' => $this->applications->wards($state, $lga)]);
    }
}
