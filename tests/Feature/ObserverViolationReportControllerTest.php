<?php

namespace Tests\Feature;

use App\Models\ObserverViolationReport;
use App\Models\OsunPollingUnit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ObserverViolationReportControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('observer_violations.evidence_disk', 'observer-violations-private');
        Storage::fake('observer-violations-private');

        OsunPollingUnit::query()->create([
            'state' => 'Osun',
            'lga' => 'Osogbo',
            'ward' => 'Akepe / Eketa',
            'polling_unit_code' => '29/30/01/016',
            'polling_unit_name' => '1 ASUBIARO STR',
            'source_url' => 'https://example.com/osun',
        ]);
    }

    public function test_it_requires_core_observer_location_violation_and_consent_fields(): void
    {
        $response = $this->postJson(route('observer-violations.reports.store'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'observer_full_name',
                'observer_phone_number',
                'observer_email',
                'observer_assigned_lga',
                'incident_lga',
                'violation_category',
                'evidence_consent_confirmed',
            ]);
    }

    public function test_it_rejects_unknown_polling_unit_combinations(): void
    {
        $payload = $this->validPayload([
            'incident_polling_unit_name' => 'Wrong Unit',
        ]);

        $response = $this->postJson(route('observer-violations.reports.store'), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['incident_polling_unit_code']);
    }

    public function test_it_stores_submitted_report_and_private_evidence_metadata(): void
    {
        $file = UploadedFile::fake()->image('evidence.jpg');

        $response = $this->post(route('observer-violations.reports.store'), array_merge(
            $this->validPayload(),
            ['evidence_files' => [$file]]
        ));

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 'submitted')
            ->assertJsonPath('evidence.0.original_filename', 'evidence.jpg')
            ->assertJsonPath('evidence.0.upload_status', 'stored');

        $report = ObserverViolationReport::query()->with('evidence')->firstOrFail();

        $this->assertSame('submitted', $report->status);
        $this->assertSame('voter intimidation or harassment', $report->violation_category);
        $this->assertSame('29/30/01/016', $report->incident_polling_unit_code);
        $this->assertSame('1 ASUBIARO STR', $report->incident_polling_unit_name);
        $this->assertCount(1, $report->evidence);
        $this->assertSame('observer-violations-private', $report->evidence->first()->storage_disk);
        $this->assertSame('stored', $report->evidence->first()->upload_status);
        $this->assertNotNull($report->evidence->first()->checksum);

        Storage::disk('observer-violations-private')->assertExists($report->evidence->first()->object_path);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'observer_full_name' => 'Observer One',
            'observer_phone_number' => '08030000000',
            'observer_email' => 'observer@example.com',
            'observer_organisation' => 'Dataphyte',
            'observer_id_or_deployment_code' => 'OBS-001',
            'observer_assigned_state' => 'Osun',
            'observer_assigned_lga' => 'Osogbo',
            'observer_assigned_ward' => 'Akepe / Eketa',
            'observer_assigned_polling_unit_code' => '29/30/01/016',
            'observer_assigned_polling_unit_name' => '1 ASUBIARO STR',
            'observer_role' => 'Field Observer',
            'observer_verification_status' => 'pending verification',
            'incident_state' => 'Osun',
            'incident_lga' => 'Osogbo',
            'incident_ward' => 'Akepe / Eketa',
            'incident_polling_unit_code' => '29/30/01/016',
            'incident_polling_unit_name' => '1 ASUBIARO STR',
            'incident_address_or_landmark' => 'Near the junction',
            'incident_gps_latitude' => '7.7712345',
            'incident_gps_longitude' => '4.5567890',
            'incident_date' => '2026-08-15',
            'incident_time_observed' => '14:30',
            'incident_is_ongoing' => '1',
            'violation_category' => 'voter intimidation or harassment',
            'incident_description' => 'Threats were directed at voters near the polling queue.',
            'evidence_description' => 'One photo and field notes.',
            'witness_statement' => 'A nearby resident confirmed the incident.',
            'external_references' => ['https://example.com/reference'],
            'evidence_consent_confirmed' => '1',
        ], $overrides);
    }
}
