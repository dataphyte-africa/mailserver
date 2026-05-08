<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\CreatesApplication;

class NewsletterCampaignLifecycleTest extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.default', 'array');
        config()->set('queue.default', 'sync');
    }

    public function test_finalize_marks_campaign_partial_when_failures_remain(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => 'sending',
            'sent_at' => now(),
            'total_recipients' => 2,
        ]);

        CampaignSend::factory()->for($campaign)->create(['status' => 'sent']);
        CampaignSend::factory()->for($campaign)->failed()->create([
            'bounce_reason' => 'App\\Jobs\\Newsletter\\SendNewsletterEmailJob has been attempted too many times.',
        ]);

        Artisan::call('campaigns:finalize');

        $this->assertSame('partial', $campaign->fresh()->status);
    }

    public function test_finalize_marks_campaign_sent_when_no_failures_or_queued_sends_remain(): void
    {
        $campaign = Campaign::factory()->create([
            'status' => 'sending',
            'sent_at' => now(),
            'total_recipients' => 2,
        ]);

        CampaignSend::factory()->for($campaign)->delivered()->create();
        CampaignSend::factory()->for($campaign)->opened()->create();

        Artisan::call('campaigns:finalize');

        $this->assertSame('sent', $campaign->fresh()->status);
    }

    public function test_send_queued_command_requeues_retryable_failed_sends_and_processes_them(): void
    {
        Mail::fake();

        $campaign = Campaign::factory()->create([
            'status' => 'sending',
            'sent_at' => now(),
            'total_recipients' => 1,
        ]);

        $subscriber = Subscriber::factory()->create();

        $send = CampaignSend::factory()->for($campaign)->for($subscriber)->failed()->create([
            'elastic_email_transaction_id' => null,
            'bounce_reason' => 'App\\Jobs\\Newsletter\\SendNewsletterEmailJob has been attempted too many times.',
        ]);

        Artisan::call('campaigns:send-queued', [
            '--campaign' => $campaign->id,
            '--retry-failed' => true,
        ]);

        $send = $send->fresh();

        $this->assertSame('sent', $send->status);
        $this->assertNull($send->failed_at);
        $this->assertNull($send->bounce_reason);
        $this->assertNotNull($send->sent_at);
        $this->assertSame('sent', $campaign->fresh()->status);
    }

    public function test_send_queued_command_does_not_requeue_non_retryable_failures(): void
    {
        Mail::fake();

        $campaign = Campaign::factory()->create([
            'status' => 'sending',
            'sent_at' => now(),
            'total_recipients' => 1,
        ]);

        $subscriber = Subscriber::factory()->create();

        $send = CampaignSend::factory()->for($campaign)->for($subscriber)->failed()->create([
            'elastic_email_transaction_id' => null,
            'bounce_reason' => 'Mailbox unavailable. The server response was: 5.1.1 user unknown',
        ]);

        Artisan::call('campaigns:send-queued', [
            '--campaign' => $campaign->id,
            '--retry-failed' => true,
        ]);

        $send = $send->fresh();

        $this->assertSame('failed', $send->status);
        $this->assertSame('Mailbox unavailable. The server response was: 5.1.1 user unknown', $send->bounce_reason);
        $this->assertSame('partial', $campaign->fresh()->status);
    }
}
