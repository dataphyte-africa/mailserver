<?php

namespace Tests\Feature;

use App\Mail\FocalPointApplicationReceivedMail;
use App\Mail\FocalPointApplicationResumeMail;
use App\Models\FocalPointApplication;
use App\Models\FocalPointApplicationDraft;
use App\Services\Foundation\FocalPointApplicationContent;
use App\Services\Foundation\FocalPointApplicationService;
use App\Services\Foundation\GoogleDriveApplicationStorage;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Mockery;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\User as StatamicUser;
use Statamic\Http\Middleware\CP\Authorize;
use Statamic\Http\Middleware\CP\CountUsers;
use Tests\TestCase;

class FocalPointApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDate(2026, 10, 20)->setTime(12, 0));
        Cache::flush();
        Mail::fake();
        config()->set('focal_point_applications.google_drive', [
            'client_id' => 'test-client', 'client_secret' => 'test-secret',
            'refresh_token' => 'test-refresh', 'cv_folder_id' => 'cv-folder',
            'certificate_folder_id' => 'certificate-folder',
        ]);
        Http::fake([
            'election.dataphyte.com/api/v1/locations/states' => Http::response(['data' => [
                ['id' => 1, 'name' => 'Osun'], ['id' => 2, 'name' => 'Connecticut'],
                ['id' => 3, 'name' => 'FCT (Abuja)'],
            ]]),
            'election.dataphyte.com/api/v1/locations/states/1/lgas' => Http::response(['data' => [
                ['id' => 10, 'name' => 'Osogbo', 'state_id' => 1],
            ]]),
            'election.dataphyte.com/api/v1/locations/lgas/10/registration-areas' => Http::response(['data' => [
                ['id' => 100, 'name' => 'Akepe', 'lga_id' => 10],
            ]]),
        ]);
    }

    public function test_public_page_uses_corrected_deadline_and_filters_foreign_states(): void
    {
        $this->get(route('focal-points.applications.show'))
            ->assertOk()->assertSee('Thursday, 29 October 2026')
            ->assertSee('assets/foundation/Dataphyte%20Foundation%20Logo.png')
            ->assertSee('Personal information')
            ->assertSee('Experience')
            ->assertSee('Coordination')
            ->assertSee('Terms')
            ->assertDontSee('Applications are reviewed by authorised staff only.');

        $this->getJson(route('focal-points.applications.locations.states'))
            ->assertOk()->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'FCT (Abuja)')
            ->assertJsonPath('data.1.name', 'Osun');
    }

    public function test_submission_requires_separate_documents_and_rejects_mismatched_locations(): void
    {
        $this->postJson(route('focal-points.applications.store'), $this->payload([
            'cv' => null,
            'certificate' => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['cv', 'certificate']);

        $this->postJson(route('focal-points.applications.store'), $this->payload([
            'ward_id' => 999,
        ]))->assertUnprocessable()->assertJsonValidationErrors('ward_id');

        $this->assertDatabaseCount('focal_point_applications', 0);
    }

    public function test_submission_stores_drive_links_and_super_admin_can_export_them(): void
    {
        $drive = Mockery::mock(GoogleDriveApplicationStorage::class);
        $drive->shouldReceive('configured')->andReturn(true);
        $drive->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class), 'cv')
            ->andReturn(['id' => 'cv-id', 'url' => 'https://drive.google.com/file/d/cv-id/view', 'filename' => 'cv.pdf']);
        $drive->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class), 'certificate')
            ->andReturn(['id' => 'cert-id', 'url' => 'https://drive.google.com/file/d/cert-id/view', 'filename' => 'certificate.pdf']);
        app()->instance(GoogleDriveApplicationStorage::class, $drive);

        $this->post(route('focal-points.applications.store'), $this->payload())
            ->assertRedirect(route('focal-points.applications.show'));

        $application = FocalPointApplication::query()->firstOrFail();
        $this->assertSame('Osun', $application->answers['state']);
        $this->assertSame('Osogbo', $application->answers['lga']);
        $this->assertSame('Akepe', $application->answers['ward']);
        $this->assertSame('cv-id', $application->cv_drive_id);
        $this->assertSame('cert-id', $application->certificate_drive_id);
        $this->assertSame(app(FocalPointApplicationContent::class)->torVersion(), $application->tor_version);
        $this->assertStringContainsString('Role of State Focal Points', $application->tor_html);
        $this->assertNotNull($application->tor_accepted_at);
        $this->assertSame('pending', $application->confirmation_email_status);
        Mail::assertQueued(FocalPointApplicationReceivedMail::class, function (FocalPointApplicationReceivedMail $mail) use ($application): bool {
            return $mail->hasTo($application->email)
                && $mail->fullName === $application->full_name
                && $mail->reference === $application->uuid
                && $mail->headers()->text['X-Focal-Point-Application-Id'] === $application->uuid
                && str_contains($mail->render(), $application->uuid);
        });

        $this->withoutMiddleware(CountUsers::class);
        $this->withoutMiddleware(Authorize::class);
        $operator = StatamicUser::make()->id((string) Str::uuid())->email('focal-test-'.Str::random(8).'@example.test');
        $operator->assignRole('scoped_operator');
        $operator->save();
        try {
            $this->actingAs($operator, config('statamic.users.guards.cp', 'web'));
            $this->get(cp_route('focal-point-applications.export'))->assertForbidden();

            $admin = StatamicUser::findByEmail('admin@mailserver.test');
            $this->assertNotNull($admin);
            $this->actingAs($admin, config('statamic.users.guards.cp', 'web'));
            $this->get(cp_route('focal-point-applications.index'))->assertOk()->assertSee('Ada Observer')->assertSee('Email status')->assertSee('Pending');
            $this->get(cp_route('focal-point-applications.show', $application))->assertOk()->assertSee('View CV')->assertSee('Email status');
            $csv = $this->get(cp_route('focal-point-applications.export'))->assertOk()->streamedContent();
            $this->assertStringContainsString('https://drive.google.com/file/d/cv-id/view', $csv);
            $this->assertStringContainsString('https://drive.google.com/file/d/cert-id/view', $csv);
            $this->assertStringContainsString('tor_accepted_at', $csv);
            $this->assertStringContainsString('confirmation_email_status', $csv);

            $this->post(cp_route('focal-point-applications.status', $application), [
                'status' => 'under_review',
            ])->assertRedirect();
            $this->assertSame('under_review', $application->fresh()->status);
        } finally {
            $operator->delete();
        }
    }

    public function test_delivery_and_open_webhooks_update_only_the_matching_application(): void
    {
        config()->set('newsletter.webhook_secret', 'test-webhook-secret');
        config()->set('queue.default', 'sync');

        $application = FocalPointApplication::query()->create([
            'uuid' => (string) Str::uuid(),
            'email' => 'applicant@example.test',
            'full_name' => 'Test Applicant',
            'answers' => [],
            'cv_drive_id' => 'cv-id',
            'cv_drive_url' => 'https://drive.google.com/file/d/cv-id/view',
            'cv_filename' => 'cv.pdf',
            'certificate_drive_id' => 'cert-id',
            'certificate_drive_url' => 'https://drive.google.com/file/d/cert-id/view',
            'certificate_filename' => 'certificate.pdf',
        ]);

        $url = route('newsletter.webhook.elastic-email').'?secret=test-webhook-secret';
        $payload = [
            'to' => $application->email,
            'lifecycle_email' => 'focal_point_application_receipt',
            'focal_point_application_id' => $application->uuid,
            'date' => '2026-10-20T13:00:00Z',
        ];

        $this->postJson($url, [...$payload, 'eventtype' => 'Bounce'])->assertOk();
        $this->assertSame('failed', $application->fresh()->confirmation_email_status);

        $this->postJson($url, [...$payload, 'eventtype' => 'Delivery'])->assertOk();
        $this->assertSame('delivered', $application->fresh()->confirmation_email_status);
        $this->assertNotNull($application->fresh()->confirmation_email_delivered_at);

        $this->postJson($url, [...$payload, 'eventtype' => 'Open', 'date' => '2026-10-20T13:05:00Z'])->assertOk();
        $this->assertSame('confirmed', $application->fresh()->confirmation_email_status);
        $this->assertNotNull($application->fresh()->confirmation_email_opened_at);

        $this->postJson($url, [...$payload, 'eventtype' => 'Delivery'])->assertOk();
        $this->assertSame('confirmed', $application->fresh()->confirmation_email_status);

        $this->postJson($url, [...$payload, 'to' => 'someone-else@example.test', 'eventtype' => 'Bounce'])->assertOk();
        $this->assertSame('confirmed', $application->fresh()->confirmation_email_status);

        $this->postJson(route('newsletter.webhook.elastic-email'), [...$payload, 'eventtype' => 'Bounce'])->assertUnauthorized();
        $this->assertSame('confirmed', $application->fresh()->confirmation_email_status);
    }

    public function test_drive_failure_does_not_store_a_submission(): void
    {
        $drive = Mockery::mock(GoogleDriveApplicationStorage::class);
        $drive->shouldReceive('configured')->andReturn(true);
        $drive->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class), 'cv')
            ->andReturn(['id' => 'cv-id', 'url' => 'https://drive.google.com/file/d/cv-id/view', 'filename' => 'cv.pdf']);
        $drive->shouldReceive('upload')->once()->with(Mockery::type(UploadedFile::class), 'certificate')
            ->andThrow(new \RuntimeException('Provider unavailable'));
        $drive->shouldReceive('delete')->once()->with('cv-id');
        app()->instance(GoogleDriveApplicationStorage::class, $drive);

        $this->post(route('focal-points.applications.store'), $this->payload())
            ->assertRedirect()->assertSessionHasErrors('documents');
        $this->assertDatabaseCount('focal_point_applications', 0);
        Mail::assertNothingOutgoing();
    }

    public function test_google_drive_upload_uses_the_configured_folder_and_oauth_token(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token']),
            'https://www.googleapis.com/upload/drive/v3/files*' => Http::response([
                'id' => 'cv-file-id', 'webViewLink' => 'https://drive.google.com/file/d/cv-file-id/view',
            ]),
        ]);

        $result = app(GoogleDriveApplicationStorage::class)->upload(
            UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'), 'cv',
        );

        $this->assertSame('cv-file-id', $result['id']);
        Http::assertSent(fn (HttpRequest $request): bool => str_contains($request->url(), '/upload/drive/v3/files')
            && str_contains($request->body(), '"parents":["cv-folder"]')
            && $request->hasHeader('Authorization', 'Bearer access-token'));
    }

    public function test_non_resident_and_missing_consent_are_rejected(): void
    {
        $this->postJson(route('focal-points.applications.store'), $this->payload([
            'resident_in_state' => 'no', 'application_consent' => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['resident_in_state', 'application_consent']);
    }

    public function test_terms_must_be_acknowledged_and_current_before_submission(): void
    {
        $this->postJson(route('focal-points.applications.store'), $this->payload([
            'tor_accepted' => null,
        ]))->assertUnprocessable()->assertJsonValidationErrors('tor_accepted');

        $previousVersion = app(FocalPointApplicationContent::class)->torVersion();
        $variables = GlobalSet::findByHandle('focal_point_application')->inDefaultSite();
        $variables->data([...$variables->data()->toArray(), 'tor_html' => '<p>Updated terms for applicants.</p>']);
        $variables->save();
        $this->get(route('focal-points.applications.show'))->assertOk()
            ->assertSee('Updated terms for applicants.')
            ->assertSee('id="observation-experience-field" hidden', false)
            ->assertSee('id="coordination-experience-field" hidden', false);
        $this->postJson(route('focal-points.applications.store'), $this->payload([
            'tor_version' => $previousVersion,
        ]))->assertUnprocessable()->assertJsonValidationErrors('tor_version');
        $this->assertDatabaseCount('focal_point_applications', 0);
    }

    public function test_saved_terms_are_ready_in_production_without_separate_approval(): void
    {
        $variables = GlobalSet::findByHandle('focal_point_application')->inDefaultSite();
        $variables->data([...$variables->data()->toArray(), 'approved_tor_version' => null]);
        $variables->save();

        $originalEnvironment = app()->environment();
        try {
            app()->detectEnvironment(fn () => 'production');
            $this->assertTrue(app(FocalPointApplicationService::class)->isReady());
            $this->get(route('focal-points.applications.show'))->assertOk()
                ->assertSee('id="submit-button"', false)
                ->assertDontSee('id="submit-button" disabled', false)
                ->assertDontSee('Draft for review');
        } finally {
            app()->detectEnvironment(fn () => $originalEnvironment);
        }

        $this->assertTrue(app(FocalPointApplicationService::class)->isReady());

        $variables->data([...$variables->data()->toArray(), 'tor_html' => '']);
        $variables->save();
        $this->assertFalse(app(FocalPointApplicationService::class)->isReady());
        $this->postJson(route('focal-points.applications.store'), $this->payload())->assertStatus(503);
    }

    public function test_submission_closes_after_the_final_minute_in_lagos(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-30 00:00:00', 'Africa/Lagos'));

        $this->postJson(route('focal-points.applications.store'), $this->payload())->assertGone();
        $this->assertDatabaseCount('focal_point_applications', 0);
    }

    public function test_receipt_remains_visible_after_the_deadline(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-30 00:00:00', 'Africa/Lagos'));

        $this->withSession(['application_received' => 'receipt-reference'])
            ->get(route('focal-points.applications.show'))
            ->assertOk()->assertSee('receipt-reference')
            ->assertDontSee('Applications are closed.');
    }

    public function test_admin_can_edit_copy_close_reopen_and_save_changed_terms(): void
    {
        $variables = GlobalSet::findByHandle('focal_point_application')->inDefaultSite();
        $variables->data([
            ...$variables->data()->toArray(),
            'hero_intro' => 'A new recruitment introduction.',
            'intro' => 'A revised role description.',
            'accepting_submissions' => false,
            'tor_html' => '<h2>Updated terms</h2><p>Read these requirements.</p>',
        ]);
        $variables->save();

        $this->get(route('focal-points.applications.show'))->assertOk()->assertSee('Submissions closed');
        $this->postJson(route('focal-points.applications.store'), $this->payload())->assertGone();

        $variables->data([
            ...$variables->data()->toArray(),
            'accepting_submissions' => true,
            'closes_at' => '2026-11-29 23:59:00',
        ]);
        $variables->save();
        $this->get(route('focal-points.applications.show'))->assertOk()
            ->assertSee('A new recruitment introduction.')
            ->assertSee('A revised role description.')
            ->assertSee('Updated terms');
        $this->assertTrue(app(FocalPointApplicationService::class)->isReady());

        $this->withoutMiddleware(CountUsers::class);
        $this->withoutMiddleware(Authorize::class);
        $admin = StatamicUser::findByEmail('admin@mailserver.test');
        $this->actingAs($admin, config('statamic.users.guards.cp', 'web'));
        $this->get(cp_route('product-forms.index'))->assertOk()
            ->assertDontSee('Approve current terms')
            ->assertDontSee('Needs approval');

        $variables->data([...$variables->data()->toArray(), 'tor_html' => '<p>The payment amount and breakdown will be inserted only after confirmation.</p>']);
        $variables->save();
        $this->assertTrue(app(FocalPointApplicationService::class)->isReady());

        $variables->data([...$variables->data()->toArray(), 'tor_html' => '<p>Changed again.</p>']);
        $variables->save();
        $this->assertTrue(app(FocalPointApplicationService::class)->isReady());
    }

    public function test_completed_personal_step_is_saved_as_a_private_draft(): void
    {
        $response = $this->postJson(route('focal-points.applications.drafts.step'), [
            ...Arr::only($this->payload(), ['full_name', 'primary_phone', 'other_phone', 'email', 'gender', 'age', 'has_disability', 'education', 'linkedin_url', 'state_id', 'lga_id', 'ward_id', 'residential_address', 'resident_in_state', 'residence_length', 'party_member', 'candidate_2027']),
            'step' => 0,
        ]);

        $response->assertOk()->assertJsonPath('answers.email', 'ada@example.test');
        $draft = FocalPointApplicationDraft::query()->sole();
        $this->assertSame('ada@example.test', $draft->email);
        $this->assertSame(0, $draft->last_completed_step);
        $this->assertTrue($draft->expires_at->isFuture());
    }

    public function test_resume_link_is_single_use_and_does_not_reveal_draft_existence(): void
    {
        $draft = FocalPointApplicationDraft::query()->create([
            'uuid' => (string) Str::uuid(), 'email' => 'ada@example.test', 'answers' => ['full_name' => 'Ada Observer'],
            'expires_at' => now()->addDays(14),
        ]);

        $this->postJson(route('focal-points.applications.drafts.resume.request'), ['email' => 'missing@example.test'])
            ->assertOk()->assertJsonPath('message', 'If an unfinished application matches that email, a resume link has been sent.');
        Mail::assertNothingOutgoing();

        $this->postJson(route('focal-points.applications.drafts.resume.request'), ['email' => $draft->email])
            ->assertOk()->assertJsonPath('message', 'If an unfinished application matches that email, a resume link has been sent.');
        Mail::assertQueued(FocalPointApplicationResumeMail::class);

        $token = app(\App\Services\Foundation\FocalPointApplicationDraftService::class)->createResumeToken($draft);
        $this->get(route('focal-points.applications.drafts.resume', ['uuid' => $draft->uuid, 'token' => $token]))
            ->assertRedirect(route('focal-points.applications.show'))->assertSessionHas('focal_point_draft_uuid', $draft->uuid);
        $this->get(route('focal-points.applications.drafts.resume', ['uuid' => $draft->uuid, 'token' => $token]))
            ->assertRedirect(route('focal-points.applications.show'))->assertSessionHasErrors('resume');
    }

    public function test_email_entry_identifies_an_active_draft_before_offering_a_resume_link(): void
    {
        FocalPointApplicationDraft::query()->create([
            'uuid' => (string) Str::uuid(), 'email' => 'ada@example.test', 'answers' => ['full_name' => 'Ada Observer'],
            'expires_at' => now()->addDays(14),
        ]);

        $this->postJson(route('focal-points.applications.drafts.lookup'), ['email' => 'ada@example.test'])
            ->assertOk()->assertJsonPath('has_draft', true);
        $this->postJson(route('focal-points.applications.drafts.lookup'), ['email' => 'new@example.test'])
            ->assertOk()->assertJsonPath('has_draft', false);
    }

    public function test_final_submission_can_use_confirmed_draft_documents_without_reuploading(): void
    {
        $draft = FocalPointApplicationDraft::query()->create([
            'uuid' => (string) Str::uuid(), 'email' => 'ada@example.test', 'answers' => [], 'last_completed_step' => 2,
            'cv_drive_id' => 'draft-cv-id', 'cv_drive_url' => 'https://drive.google.com/file/d/draft-cv-id/view', 'cv_filename' => 'saved-cv.pdf',
            'certificate_drive_id' => 'draft-cert-id', 'certificate_drive_url' => 'https://drive.google.com/file/d/draft-cert-id/view', 'certificate_filename' => 'saved-certificate.pdf',
            'expires_at' => now()->addDays(14),
        ]);
        $drive = Mockery::mock(GoogleDriveApplicationStorage::class);
        $drive->shouldReceive('configured')->andReturn(true);
        $drive->shouldNotReceive('upload');
        app()->instance(GoogleDriveApplicationStorage::class, $drive);

        $this->withSession(['focal_point_draft_uuid' => $draft->uuid])
            ->post(route('focal-points.applications.store'), $this->payload(['cv' => null, 'certificate' => null]))
            ->assertRedirect(route('focal-points.applications.show'));

        $application = FocalPointApplication::query()->sole();
        $this->assertSame('draft-cv-id', $application->cv_drive_id);
        $this->assertSame('draft-cert-id', $application->certificate_drive_id);
        $this->assertDatabaseMissing('focal_point_application_drafts', ['id' => $draft->id]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Ada Observer', 'primary_phone' => '08030000000',
            'email' => 'ada@example.test', 'gender' => 'female', 'age' => 34,
            'has_disability' => 'no', 'education' => 'masters',
            'state_id' => 1, 'lga_id' => 10, 'ward_id' => 100,
            'residential_address' => '22 Example Road, Osogbo',
            'resident_in_state' => 'yes', 'residence_length' => 'over_5',
            'party_member' => 'no', 'candidate_2027' => 'no',
            'observed_before' => 'no', 'coordinated_before' => 'no',
            'available_for_training' => 'yes',
            'recruitment_plan' => 'I would recruit and train impartial observers across the state.',
            'reporting_plan' => 'I would verify reports and submit them on time.',
            'coordination_challenges' => 'Connectivity is a risk, so I would prepare an offline fallback.',
            'polling_unit_scenario' => 'I would record the time, absence of officials, voters and security personnel, then report verified facts.',
            'tools' => ['excel', 'google_forms'],
            'application_consent' => '1',
            'tor_accepted' => '1',
            'tor_version' => app(FocalPointApplicationContent::class)->torVersion(),
            'cv' => UploadedFile::fake()->create('cv.pdf', 20, 'application/pdf'),
            'certificate' => UploadedFile::fake()->create('certificate.pdf', 20, 'application/pdf'),
        ], $overrides);
    }
}
