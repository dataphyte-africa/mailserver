<?php

namespace App\Services\Foundation;

use App\Models\FocalPointApplication;
use App\Models\FocalPointApplicationDraft;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FocalPointApplicationService
{
    private const NIGERIAN_STATES = [
        'abia', 'adamawa', 'akwa ibom', 'anambra', 'bauchi', 'bayelsa', 'benue', 'borno',
        'cross river', 'delta', 'ebonyi', 'edo', 'ekiti', 'enugu', 'gombe', 'imo', 'jigawa',
        'kaduna', 'kano', 'katsina', 'kebbi', 'kogi', 'kwara', 'lagos', 'nasarawa', 'niger',
        'ogun', 'ondo', 'osun', 'oyo', 'plateau', 'rivers', 'sokoto', 'taraba', 'yobe', 'zamfara',
        'fct', 'fct (abuja)', 'federal capital territory', 'abuja',
    ];

    public function __construct(
        private readonly ElectionLocationService $locations,
        private readonly GoogleDriveApplicationStorage $drive,
        private readonly FocalPointApplicationContent $content,
    ) {}

    public function closesAt(): CarbonImmutable
    {
        return $this->content->closesAt();
    }

    public function isOpen(): bool
    {
        return $this->content->isOpen();
    }

    public function isReady(): bool
    {
        return $this->drive->configured()
            && $this->content->hasTerms();
    }

    public function states(): array
    {
        return array_values(array_filter($this->locations->states()['data'],
            fn (array $state): bool => in_array(Str::lower(trim($state['name'])), self::NIGERIAN_STATES, true)));
    }

    public function lgas(int $stateId): array
    {
        $this->state($stateId);

        return array_values(array_filter($this->locations->lgas($stateId)['data'],
            fn (array $lga): bool => (int) $lga['state_id'] === $stateId));
    }

    public function wards(int $stateId, int $lgaId): array
    {
        $this->lga($stateId, $lgaId);

        return array_values(array_filter($this->locations->wards($lgaId)['data'],
            fn (array $ward): bool => (int) $ward['lga_id'] === $lgaId));
    }

    public function rules(bool $requireDocuments = true): array
    {
        $cvMax = max(1, (int) config('focal_point_applications.cv_max_upload_kb', 1024));
        $certificateMax = max(1, (int) config('focal_point_applications.certificate_max_upload_kb', 2048));

        return [
            'full_name' => ['required', 'string', 'max:200'],
            'primary_phone' => ['required', 'string', 'max:30'],
            'other_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'between:18,120'],
            'has_disability' => ['required', Rule::in(['yes', 'no'])],
            'education' => ['required', Rule::in(['bachelors', 'postgraduate_diploma', 'masters', 'phd', 'other'])],
            'linkedin_url' => ['nullable', 'url', 'max:500'],
            'state_id' => ['required', 'integer', 'min:1'],
            'lga_id' => ['required', 'integer', 'min:1'],
            'ward_id' => ['required', 'integer', 'min:1'],
            'residential_address' => ['required', 'string', 'max:1000'],
            'resident_in_state' => ['required', Rule::in(['yes', 'no'])],
            'residence_length' => ['required', Rule::in(['under_1', '1_to_3', '4_to_5', 'over_5'])],
            'party_member' => ['required', Rule::in(['yes', 'no'])],
            'candidate_2027' => ['required', Rule::in(['yes', 'no'])],
            'observed_before' => ['required', Rule::in(['yes', 'no'])],
            'observation_experience' => ['required_if:observed_before,yes', 'nullable', 'string', 'max:3000'],
            'coordinated_before' => ['required', Rule::in(['yes', 'no'])],
            'coordination_experience' => ['required_if:coordinated_before,yes', 'nullable', 'string', 'max:3000'],
            'available_for_training' => ['required', Rule::in(['yes', 'no'])],
            'recruitment_plan' => ['required', 'string', 'max:3000'],
            'polling_unit_scenario' => ['required', 'string', 'max:2000'],
            'cv' => [$requireDocuments ? 'required' : 'nullable', 'file', 'mimes:pdf,doc,docx', "max:{$cvMax}"],
            'certificate' => [$requireDocuments ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', "max:{$certificateMax}"],
            'application_consent' => ['accepted'],
            'tor_accepted' => ['accepted'],
            'tor_version' => ['required', Rule::in([$this->content->torVersion()])],
            'website' => ['prohibited'],
        ];
    }

    /** @param array<string, mixed> $validated */
    public function submit(array $validated, FocalPointApplicationDraft $draft): FocalPointApplication
    {
        $torHtml = $this->content->torHtml();
        $torVersion = 'sha256:'.hash('sha256', $torHtml);
        if ($validated['tor_version'] !== $torVersion) {
            throw ValidationException::withMessages(['tor_version' => 'The Terms of Reference changed. Reload the form and review the current version.']);
        }

        $state = $this->state((int) $validated['state_id']);
        $lga = $this->lga((int) $validated['state_id'], (int) $validated['lga_id']);
        $ward = collect($this->wards((int) $validated['state_id'], (int) $validated['lga_id']))
            ->firstWhere('id', (int) $validated['ward_id']);
        if (! $ward) {
            throw ValidationException::withMessages(['ward_id' => 'Select a ward in the chosen LGA.']);
        }

        foreach (['recruitment_plan' => 100, 'polling_unit_scenario' => 50] as $field => $limit) {
            $words = preg_split('/\s+/u', trim($validated[$field]), -1, PREG_SPLIT_NO_EMPTY);
            if (count($words ?: []) > $limit) {
                throw ValidationException::withMessages([$field => "Use {$limit} words or fewer."]);
            }
        }

        if (! $draft->cv_drive_id || ! $draft->certificate_drive_id
            || $draft->cv_upload_status !== 'ready' || $draft->certificate_upload_status !== 'ready') {
            throw ValidationException::withMessages(['documents' => 'Wait for both document uploads to finish before submitting your application.']);
        }

        return DB::transaction(function () use ($validated, $state, $lga, $ward, $draft, $torHtml, $torVersion): FocalPointApplication {
            $answers = Arr::except($validated, ['cv', 'certificate', 'website', 'application_consent', 'tor_accepted', 'tor_version']);
            $answers['state'] = $state['name'];
            $answers['lga'] = $lga['name'];
            $answers['ward'] = $ward['name'];
            return FocalPointApplication::query()->create([
                'uuid' => (string) Str::uuid(),
                'email' => Str::lower($validated['email']),
                'full_name' => $validated['full_name'],
                'answers' => $answers,
                'tor_version' => $torVersion,
                'tor_html' => $torHtml,
                'tor_accepted_at' => now(),
                'cv_drive_id' => $draft->cv_drive_id,
                'cv_drive_url' => $draft->cv_drive_url,
                'cv_filename' => $draft->cv_filename,
                'certificate_drive_id' => $draft->certificate_drive_id,
                'certificate_drive_url' => $draft->certificate_drive_url,
                'certificate_filename' => $draft->certificate_filename,
            ]);
        });
    }

    private function state(int $stateId): array
    {
        $state = collect($this->states())->firstWhere('id', $stateId);
        if (! $state) {
            throw ValidationException::withMessages(['state_id' => 'Select a Nigerian state.']);
        }

        return $state;
    }

    private function lga(int $stateId, int $lgaId): array
    {
        $lga = collect($this->lgas($stateId))->firstWhere('id', $lgaId);
        if (! $lga) {
            throw ValidationException::withMessages(['lga_id' => 'Select an LGA in the chosen state.']);
        }

        return $lga;
    }
}
