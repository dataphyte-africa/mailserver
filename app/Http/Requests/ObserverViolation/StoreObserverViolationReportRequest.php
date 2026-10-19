<?php

namespace App\Http\Requests\ObserverViolation;

use App\Models\OsunPollingUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreObserverViolationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedMimes = implode(',', config('observer_violations.evidence_allowed_mime_types', []));

        return [
            'observer_full_name' => ['required', 'string', 'max:255'],
            'observer_phone_number' => ['required', 'string', 'max:30'],
            'observer_email' => ['required', 'email', 'max:255'],
            'observer_organisation' => ['required', 'string', 'max:255'],
            'observer_id_or_deployment_code' => ['required', 'string', 'max:255'],
            'observer_assigned_state' => ['required', 'string', Rule::in(['Osun'])],
            'observer_assigned_lga' => ['required', 'string', 'max:255'],
            'observer_assigned_ward' => ['required', 'string', 'max:255'],
            'observer_assigned_polling_unit_code' => ['required', 'string', 'max:50'],
            'observer_assigned_polling_unit_name' => ['required', 'string', 'max:255'],
            'observer_role' => ['required', 'string', 'max:255'],
            'observer_verification_status' => ['required', 'string', 'max:100'],
            'incident_state' => ['required', 'string', Rule::in(['Osun'])],
            'incident_lga' => ['required', 'string', 'max:255'],
            'incident_ward' => ['required', 'string', 'max:255'],
            'incident_polling_unit_code' => ['required', 'string', 'max:50'],
            'incident_polling_unit_name' => ['required', 'string', 'max:255'],
            'incident_address_or_landmark' => ['required', 'string', 'max:500'],
            'incident_gps_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'incident_gps_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            'incident_time_observed' => ['required', 'date_format:H:i'],
            'incident_is_ongoing' => ['required', 'boolean'],
            'violation_category' => ['required', 'string', Rule::in([(string) config('observer_violations.violation_category')])],
            'incident_description' => ['required', 'string'],
            'evidence_description' => ['nullable', 'string'],
            'witness_statement' => ['nullable', 'string'],
            'external_references' => ['nullable', 'array'],
            'external_references.*' => ['nullable', 'url:http,https'],
            'evidence_consent_confirmed' => ['accepted'],
            'evidence_files' => ['nullable', 'array'],
            'evidence_files.*' => [
                'file',
                'max:' . max(1, (int) config('observer_violations.evidence_max_file_size_kb', 20480)),
                'mimetypes:' . $allowedMimes,
            ],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $this->validatePollingUnitSelection(
                    $validator,
                    prefix: 'observer_assigned',
                    label: 'assigned polling unit'
                );

                $this->validatePollingUnitSelection(
                    $validator,
                    prefix: 'incident',
                    label: 'incident polling unit'
                );
            },
        ];
    }

    private function validatePollingUnitSelection($validator, string $prefix, string $label): void
    {
        $state = (string) $this->input("{$prefix}_state");
        $lga = (string) $this->input("{$prefix}_lga");
        $ward = (string) $this->input("{$prefix}_ward");
        $code = (string) $this->input("{$prefix}_polling_unit_code");
        $name = (string) $this->input("{$prefix}_polling_unit_name");

        if ($state === '' || $lga === '' || $ward === '' || $code === '' || $name === '') {
            return;
        }

        $exists = OsunPollingUnit::query()
            ->where('state', $state)
            ->where('lga', $lga)
            ->where('ward', $ward)
            ->where('polling_unit_code', $code)
            ->where('polling_unit_name', $name)
            ->exists();

        if (! $exists) {
            $validator->errors()->add("{$prefix}_polling_unit_code", "The selected {$label} is invalid.");
        }
    }
}
