<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Mail\FocalPointApplicationReceivedMail;
use App\Mail\FocalPointApplicationResumeMail;
use App\Models\FocalPointApplication;
use App\Models\FocalPointApplicationDraft;
use App\Services\Foundation\FocalPointApplicationContent;
use App\Services\Foundation\FocalPointApplicationDraftService;
use App\Services\Foundation\FocalPointApplicationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class FocalPointApplicationController extends Controller
{
    public function __construct(
        private readonly FocalPointApplicationService $applications,
        private readonly FocalPointApplicationContent $content,
        private readonly FocalPointApplicationDraftService $drafts,
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
            'draft' => $this->drafts->fromSession(session('focal_point_draft_uuid')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->applications->isOpen(), 410, 'Applications have closed.');
        abort_unless($this->applications->isReady(), 503, 'Applications are not accepting document uploads yet.');

        if ($this->hasSubmittedApplication((string) $request->input('email'))) {
            return back()->withInput($request->except(['cv', 'certificate']))
                ->withErrors(['email' => 'An application has already been submitted with this email address. Check your inbox for the confirmation email.']);
        }

        $draft = $this->drafts->fromSession($request->session()->get('focal_point_draft_uuid'));
        $usesDraft = $draft && Str::lower((string) $request->input('email')) === $draft->email && $this->drafts->hasDocuments($draft);
        $validated = $request->validate($this->applications->rules(! $usesDraft));

        try {
            $application = $this->applications->submit(
                $validated,
                $request->file('cv'),
                $request->file('certificate'),
                $usesDraft ? $draft : null,
            );
        } catch (RuntimeException) {
            return back()->withInput($request->except(['cv', 'certificate']))
                ->withErrors(['documents' => 'We could not save your documents. Please try again.']);
        }

        if ($usesDraft) {
            $this->drafts->deleteAfterSubmission($draft);
            $request->session()->forget('focal_point_draft_uuid');
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

    public function saveDraftStep(Request $request): JsonResponse
    {
        abort_unless($this->applications->isOpen(), 410, 'Applications have closed.');
        abort_unless($this->applications->isReady(), 503, 'Applications are not accepting document uploads yet.');

        $step = (int) $request->input('step');
        $fields = match ($step) {
            0 => ['full_name', 'primary_phone', 'other_phone', 'email', 'gender', 'age', 'has_disability', 'education', 'linkedin_url', 'state_id', 'lga_id', 'ward_id', 'residential_address', 'resident_in_state', 'residence_length', 'party_member', 'candidate_2027', 'website'],
            1 => ['observed_before', 'observation_experience', 'coordinated_before', 'coordination_experience', 'available_for_training'],
            2 => ['recruitment_plan', 'reporting_plan', 'coordination_challenges', 'polling_unit_scenario', 'tools', 'tools_other', 'application_consent', 'cv', 'certificate'],
            default => throw ValidationException::withMessages(['step' => 'Choose a valid application section.']),
        };
        $validated = $request->validate(Arr::only($this->applications->rules(false), $fields));
        if ($step === 0 && $this->hasSubmittedApplication($validated['email'])) {
            throw ValidationException::withMessages(['email' => 'An application has already been submitted with this email address. Check your inbox for the confirmation email.']);
        }
        if ($step === 2 && in_array('other', $validated['tools'] ?? [], true) && blank($validated['tools_other'] ?? null)) {
            throw ValidationException::withMessages(['tools_other' => 'Describe the other tools you can use.']);
        }
        $draft = $this->drafts->fromSession($request->session()->get('focal_point_draft_uuid'));
        if ($step === 2 && (! ($request->hasFile('cv') || $draft?->cv_drive_id) || ! ($request->hasFile('certificate') || $draft?->certificate_drive_id))) {
            throw ValidationException::withMessages(['documents' => 'Upload both documents before continuing.']);
        }
        $draft = $this->drafts->save($draft, $step, $validated, $request->file('cv'), $request->file('certificate'));

        $request->session()->put('focal_point_draft_uuid', $draft->uuid);

        return response()->json($this->draftPayload($draft));
    }

    public function requestResume(Request $request): JsonResponse
    {
        $email = Str::lower((string) $request->validate(['email' => ['required', 'email:rfc', 'max:255']])['email']);
        $draft = FocalPointApplicationDraft::query()
            ->where('email', $email)->where('expires_at', '>', now())->orderByDesc('id')->first();

        if ($draft) {
            $token = $this->drafts->createResumeToken($draft);
            Mail::to($draft->email)->queue(new FocalPointApplicationResumeMail(
                route('focal-points.applications.drafts.resume', ['uuid' => $draft->uuid, 'token' => $token]),
            ));
        }

        return response()->json(['message' => 'If an unfinished application matches that email, a resume link has been sent.']);
    }

    public function lookupDraft(Request $request): JsonResponse
    {
        $email = Str::lower((string) $request->validate(['email' => ['required', 'email:rfc', 'max:255']])['email']);
        $hasDraft = FocalPointApplicationDraft::query()
            ->where('email', $email)
            ->where('expires_at', '>', now())
            ->exists();

        return response()->json([
            // The public entry flow intentionally tells an applicant when this email has an active draft.
            'has_draft' => $hasDraft,
            'has_submission' => ! $hasDraft && $this->hasSubmittedApplication($email),
        ]);
    }

    public function resume(Request $request, string $uuid, string $token): RedirectResponse
    {
        $draft = $this->drafts->resume($uuid, $token);
        if (! $draft) {
            return redirect()->route('focal-points.applications.show')->withErrors(['resume' => 'This resume link is invalid or has expired. Request a new one.']);
        }

        $request->session()->put('focal_point_draft_uuid', $draft->uuid);

        return redirect()->route('focal-points.applications.show')->with('draft_resumed', true);
    }

    private function draftPayload(FocalPointApplicationDraft $draft): array
    {
        return [
            'uuid' => $draft->uuid,
            'answers' => $draft->answers,
            'last_completed_step' => $draft->last_completed_step,
            'cv_filename' => $draft->cv_filename,
            'certificate_filename' => $draft->certificate_filename,
        ];
    }

    private function hasSubmittedApplication(string $email): bool
    {
        return filled($email) && FocalPointApplication::query()
            ->where('email', Str::lower($email))
            ->exists();
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
