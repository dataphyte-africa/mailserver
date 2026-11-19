<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\TorFlow;
use App\Models\TorInvitation;
use App\Services\Mail\FirstPartyClickTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirstPartyClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tor_email_link_is_tracked_through_the_secure_application_redirect(): void
    {
        $organisation = Organisation::query()->create([
            'name' => 'Dataphyte Foundation',
            'slug' => 'dataphyte-foundation',
            'status' => 'active',
        ]);
        $flow = TorFlow::query()->create([
            'organisation_id' => $organisation->id,
            'title' => 'Tracked TOR',
            'content_html' => '<p>Terms.</p>',
            'status' => 'active',
        ]);
        $invitation = TorInvitation::query()->create([
            'tor_flow_id' => $flow->id,
            'expected_name' => 'Tracked Signer',
            'email' => 'tracked@example.test',
            'token' => str_repeat('t', 80),
        ]);
        $tracking = app(FirstPartyClickTrackingService::class);
        $trackedUrl = $tracking->url('https://mail.dataphyte.com/tor/sign/'.$invitation->token, [
            'tor_invitation_id' => $invitation->id,
        ]);
        $this->assertStringStartsWith('https://', $trackedUrl);

        $this->get($trackedUrl)
            ->assertRedirect('https://mail.dataphyte.com/tor/sign/'.$invitation->token);

        $this->assertDatabaseHas('tor_invitations', [
            'id' => $invitation->id,
            'status' => 'clicked',
        ]);
        $rewritten = $tracking->rewriteHtml('<a href="https://example.test/guide">Guide</a>', [
            'tor_invitation_id' => $invitation->id,
        ]);
        $this->assertStringContainsString('/tracking/click/', $rewritten);
        $this->assertStringNotContainsString('href="https://example.test/guide"', $rewritten);
    }
}
