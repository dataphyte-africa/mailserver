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
use Illuminate\Support\Facades\Http;
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
        $this->get(route('tor.public.download', $invitation->token))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=ada-example-signed-tor-dataphyte-foundation.pdf');
        Mail::assertSent(TorCompletedMail::class, 2);
        Mail::assertSent(TorCompletedMail::class, fn (TorCompletedMail $mail): bool => $mail->copyType === 'signer');
        Mail::assertSent(TorCompletedMail::class, fn (TorCompletedMail $mail): bool => $mail->copyType === 'organisation');
        $this->assertStringContainsString('Dear Ada Example', (new TorInvitationMail($fresh->load('flow.organisation')))->render());
        $this->assertStringContainsString('Download your original signed copy', (new TorCompletedMail($fresh->load('flow.organisation'), 'signer'))->render());
        $this->assertStringContainsString('Download the organisation copy', (new TorCompletedMail($fresh->load('flow.organisation'), 'organisation'))->render());
        $csv = app(TorSignerRegisterService::class)->csv($flow->fresh());
        $this->assertStringContainsString('email,bank_name,i_confirm_these_details_are_accurate', $csv);
        $this->assertStringContainsString('Lagos,Coordinator,08000000000,signer@example.test', $csv);
        $this->assertStringContainsString('"Example Bank",Yes,signed', $csv);
        $this->assertStringContainsString(route('tor.public.download', $invitation->token), $csv);
        $this->assertStringContainsString('Log in to download the signer register', (new TorSignerRegisterMail($flow->fresh('organisation'), false, 1))->render());
        $this->get(route('tor.public.show', $invitation->token))
            ->assertOk()
            ->assertSee('Download your signed TOR PDF')
            ->assertDontSee('Complete signing');

        $missingPdfInvitation = TorInvitation::query()->create([
            'tor_flow_id' => $flow->id,
            'expected_name' => 'Missing PDF Example',
            'email' => 'missing-pdf@example.test',
            'token' => str_repeat('c', 80),
            'status' => 'signed',
            'signed_pdf_path' => 'tor-signed/missing.pdf',
        ]);
        $this->get(route('tor.public.download', $missingPdfInvitation->token))
            ->assertRedirect(route('tor.public.show', $missingPdfInvitation->token))
            ->assertSessionHasErrors('download');

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

        $deadlineInvitation = TorInvitation::query()->create([
            'tor_flow_id' => $flow->id,
            'expected_name' => 'Deadline Example',
            'email' => 'deadline@example.test',
            'token' => str_repeat('e', 80),
        ]);
        $flow->update(['signing_deadline' => now()->subMinute()]);
        $this->get(route('tor.public.show', $deadlineInvitation->token))->assertForbidden();
        $flow->update(['signing_deadline' => now()->addDay()]);
        $this->get(route('tor.public.show', $deadlineInvitation->token))->assertOk();
    }

    public function test_verified_bank_account_fields_are_saved_and_exported(): void
    {
        Storage::fake('local');
        Mail::fake();
        config(['services.paystack.secret_key' => 'test_secret']);
        Http::fake([
            'https://api.paystack.co/bank?*' => Http::response([
                'status' => true,
                'data' => [['name' => 'Example Bank', 'code' => '058']],
            ]),
            'https://api.paystack.co/bank/resolve?*' => Http::response([
                'status' => true,
                'data' => ['account_name' => 'Ada Example'],
            ]),
        ]);
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Insight',
            'slug' => 'dataphyte-insight',
            'status' => 'active',
        ]);
        $flow = TorFlow::query()->create([
            'organisation_id' => $organisation->id,
            'title' => 'Bank details TOR',
            'content_html' => '<p>Terms of reference.</p>',
            'internal_recipients' => [],
            'email_copy' => [
                'invitation' => 'Please review :tor_title, :full_name.',
                'signer_completion' => 'Thank you :full_name for completing :tor_title.',
                'organisation_completion' => ':full_name has completed :tor_title for :organisation_name.',
            ],
            'signer_fields' => [[
                'type' => 'section',
                'heading' => 'Payment details',
                'instructions' => '',
                'fields' => [[
                    'handle' => 'payment_account',
                    'label' => 'Payment account',
                    'help' => '',
                    'type' => 'bank_account',
                    'required' => true,
                ]],
            ]],
            'status' => 'active',
        ]);
        $invitation = TorInvitation::query()->create([
            'tor_flow_id' => $flow->id,
            'expected_name' => 'Ada Example',
            'email' => 'ada@example.test',
            'token' => str_repeat('d', 80),
        ]);

        $this->get(route('tor.public.show', $invitation->token))
            ->assertOk()
            ->assertSee('Search and select a bank')
            ->assertSee('Account name will appear after verification');
        $this->getJson(route('tor.public.banks', $invitation->token))
            ->assertOk()
            ->assertJsonPath('data.0.code', '058');
        $this->postJson(route('tor.public.resolve-bank-account', $invitation->token), [
            'bank_code' => '058',
            'account_number' => '0001234567',
        ])->assertOk()->assertJsonPath('data.account_name', 'Ada Example');

        $this->post(route('tor.public.sign', $invitation->token), [
            'agree' => '1', 'full_name' => 'Ada Example', 'signature_method' => 'type',
            'signer_fields' => ['payment_account' => ['bank_code' => '058', 'account_number' => '0001234567']],
        ])->assertSessionHasErrors('signer_fields.payment_account.account_name_confirmed');

        $this->post(route('tor.public.sign', $invitation->token), [
            'agree' => '1',
            'full_name' => 'Ada Example',
            'signature_method' => 'type',
            'signer_fields' => [
                'payment_account' => ['bank_code' => '058', 'account_number' => '0001234567', 'account_name_confirmed' => '1'],
            ],
        ])->assertRedirect(route('tor.public.show', $invitation->token));

        $this->assertEquals([
            'bank_name' => 'Example Bank',
            'bank_code' => '058',
            'account_number' => '0001234567',
            'account_name' => 'Ada Example',
            'account_name_confirmed' => true,
        ], $invitation->fresh()->signer_field_values['payment_account']);
        $csv = app(TorSignerRegisterService::class)->csv($flow);
        $this->assertStringContainsString('payment_account', $csv);
        $this->assertStringContainsString('Bank: Example Bank | Account number: 0001234567 | Account name: Ada Example', $csv);
        $freshInvitation = $invitation->fresh(['flow.organisation']);
        $this->assertSame('Please review :tor_title, :full_name.', $freshInvitation->flow->email_copy['invitation']);
        $this->assertStringContainsString('Please review Bank details TOR, Ada Example.', (new TorInvitationMail($freshInvitation))->render());
        $this->assertStringContainsString('Thank you Ada Example for completing Bank details TOR.', (new TorCompletedMail($freshInvitation, 'signer'))->render());
        $this->assertStringContainsString('Ada Example has completed Bank details TOR for Dataphyte Insight.', (new TorCompletedMail($freshInvitation, 'organisation'))->render());
        Http::assertSent(fn ($request) => $request->url() === 'https://api.paystack.co/bank/resolve?bank_code=058&account_number=0001234567');
    }
}
