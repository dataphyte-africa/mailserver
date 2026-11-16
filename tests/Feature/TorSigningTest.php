<?php

namespace Tests\Feature;

use App\Mail\TorCompletedMail;
use App\Mail\TorInvitationMail;
use App\Mail\TorSignerRegisterMail;
use App\Models\Organisation;
use App\Models\TorFlow;
use App\Models\TorInvitation;
use App\Services\Tor\TorSignerRegisterService;
use App\Services\Tor\TorPublicUrlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TorSigningTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_link_clicks_and_signs_without_expiring(): void
    {
        Storage::fake('local');
        Mail::fake();
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation',
            'slug' => 'dataphyte-foundation',
            'status' => 'active',
            'addresses' => [['label' => 'Main office', 'address' => '1 Example Road, Abuja']],
            'social_links' => ['linkedin' => 'https://linkedin.example.test/dataphyte'],
        ]);
        $flow = TorFlow::query()->create([
            'organisation_id' => $organisation->id,
            'title' => 'Observer TOR',
            'content_html' => '<p>Terms of reference.</p>',
            'internal_recipients' => ['internal@example.test'],
            'signer_fields' => [[
                'type' => 'section',
                'heading' => 'Bank details',
                'instructions' => 'Provide the account that should receive payment.',
                'fields' => [
                    ['handle' => 'bank_name_ab123', 'label' => 'Bank Name', 'help' => '', 'type' => 'text', 'required' => true],
                    ['handle' => 'agreement_cd456', 'label' => 'I confirm these details are accurate.', 'help' => '', 'type' => 'checkbox', 'required' => true],
                ],
            ]],
            'status' => 'active',
        ]);
        $invitation = TorInvitation::query()->create(['tor_flow_id' => $flow->id, 'expected_name' => 'Ada Example', 'state' => 'Lagos', 'position' => 'Coordinator', 'phone_number' => '08000000000', 'email' => 'signer@example.test', 'token' => str_repeat('a', 80)]);

        $this->get(route('tor.public.show', $invitation->token))->assertOk()->assertSee('Observer TOR');
        $this->assertDatabaseHas('tor_invitations', ['id' => $invitation->id, 'status' => 'clicked']);
        $this->get(route('tor.public.download', $invitation->token))
            ->assertRedirect(route('tor.public.show', $invitation->token))
            ->assertSessionHasErrors('download');

        $this->post(route('tor.public.sign', $invitation->token), [
            'agree' => '1', 'full_name' => 'Ada Example', 'signature_method' => 'type',
            'signer_fields' => ['bank_name_ab123' => 'Example Bank', 'agreement_cd456' => '1'],
        ])->assertRedirect(route('tor.public.show', $invitation->token));

        $fresh = $invitation->fresh();
        $this->assertSame('signed', $fresh->status);
        $this->assertNotEmpty($fresh->verification_code);
        $this->assertNotEmpty($fresh->document_hash);
        $this->assertSame('Example Bank', $fresh->signer_field_values['bank_name_ab123']);
        Storage::disk('local')->assertExists($fresh->signed_pdf_path);
        Mail::assertSent(TorCompletedMail::class, 2);
        Mail::assertSent(TorCompletedMail::class, fn (TorCompletedMail $mail): bool => $mail->copyType === 'signer');
        Mail::assertSent(TorCompletedMail::class, fn (TorCompletedMail $mail): bool => $mail->copyType === 'organisation');
        $this->assertStringContainsString('Dear Ada Example', (new TorInvitationMail($fresh->load('flow.organisation')))->render());
        $this->assertStringContainsString('Download your original signed copy', (new TorCompletedMail($fresh->load('flow.organisation'), 'signer'))->render());
        $this->assertStringContainsString('Download the organisation copy', (new TorCompletedMail($fresh->load('flow.organisation'), 'organisation'))->render());
        $csv = app(TorSignerRegisterService::class)->csv($flow->fresh());
        $this->assertStringContainsString('status,signed_at,unique_link,signer_copy_link,company_copy_link', $csv);
        $this->assertStringContainsString('Lagos,Coordinator,08000000000,signer@example.test', $csv);
        $this->assertStringContainsString(route('tor.public.download', $invitation->token), $csv);
        $this->assertStringContainsString('Log in to download the signer register', (new TorSignerRegisterMail($flow->fresh('organisation'), false, 1))->render());
        $this->get(route('tor.public.show', $invitation->token))
            ->assertOk()
            ->assertSee('Download your signed TOR PDF')
            ->assertDontSee('Complete signing');

        $organisation->update(['newsletter_domain' => 'nl.example.test', 'newsletter_domain_status' => 'verified']);
        $this->assertSame(
            'https://nl.example.test/tor/sign/'.$invitation->token,
            app(TorPublicUrlService::class)->signingUrl($invitation->fresh(['flow.organisation'])),
        );

        $mailFailureInvitation = TorInvitation::query()->create([
            'tor_flow_id' => $flow->id,
            'expected_name' => 'Mail Failure Example',
            'email' => 'mail-failure@example.test',
            'token' => str_repeat('b', 80),
        ]);
        Mail::shouldReceive('to')->twice()->andThrow(new \RuntimeException('Mailtrap unavailable'));

        $this->post(route('tor.public.sign', $mailFailureInvitation->token), [
            'agree' => '1', 'full_name' => 'Mail Failure Example', 'signature_method' => 'type',
            'signer_fields' => ['bank_name_ab123' => 'Example Bank', 'agreement_cd456' => '1'],
        ])->assertRedirect(route('tor.public.show', $mailFailureInvitation->token));

        $this->assertDatabaseHas('tor_invitations', ['id' => $mailFailureInvitation->id, 'status' => 'signed']);
        Storage::disk('local')->assertExists($mailFailureInvitation->fresh()->signed_pdf_path);
    }
}
