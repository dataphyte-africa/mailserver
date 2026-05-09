<?php

namespace Tests\Feature;

use App\Jobs\Newsletter\ResumeFailedCampaignSendsJob;
use App\Jobs\Newsletter\SendNewsletterEmailJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
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
        $this->withoutMiddleware();
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

    public function test_retry_failed_campaign_action_dispatches_background_resume_job(): void
    {
        Queue::fake();

        $campaign = Campaign::factory()->create([
            'status' => 'partial',
            'sent_at' => now(),
            'total_recipients' => 1,
        ]);

        CampaignSend::factory()->for($campaign)->failed()->create([
            'bounce_reason' => 'App\\Jobs\\Newsletter\\SendNewsletterEmailJob has been attempted too many times.',
        ]);

        $response = $this->post(cp_route('newsletter.campaigns.retry-failed', $campaign->id));

        $response->assertRedirect(cp_route('newsletter.campaigns.show', $campaign->id));
        $response->assertSessionHas('success', 'Queued 1 retryable failed sends for resend.');

        Queue::assertPushedOn('campaigns', ResumeFailedCampaignSendsJob::class);
    }

    public function test_retry_failed_campaign_action_rejects_when_no_retryable_failures_exist(): void
    {
        Queue::fake();

        $campaign = Campaign::factory()->create([
            'status' => 'partial',
            'sent_at' => now(),
            'total_recipients' => 1,
        ]);

        CampaignSend::factory()->for($campaign)->failed()->create([
            'bounce_reason' => 'Mailbox unavailable. The server response was: 5.1.1 user unknown',
        ]);

        $response = $this->post(cp_route('newsletter.campaigns.retry-failed', $campaign->id));

        $response->assertRedirect(cp_route('newsletter.campaigns.show', $campaign->id));
        $response->assertSessionHas('error', 'No retryable failed sends were found for this campaign.');

        Queue::assertNothingPushed();
    }

    public function test_resume_failed_campaign_job_requeues_retryable_failures_and_dispatches_resend_jobs(): void
    {
        Queue::fake();

        $campaign = Campaign::factory()->create([
            'status' => 'partial',
            'sent_at' => now(),
            'total_recipients' => 3,
        ]);

        $subscriberA = Subscriber::factory()->create();
        $subscriberB = Subscriber::factory()->create();
        $subscriberC = Subscriber::factory()->create();

        $retryableA = CampaignSend::factory()->for($campaign)->for($subscriberA)->failed()->create([
            'bounce_reason' => 'App\\Jobs\\Newsletter\\SendNewsletterEmailJob has been attempted too many times.',
        ]);

        $retryableB = CampaignSend::factory()->for($campaign)->for($subscriberB)->failed()->create([
            'bounce_reason' => '421 Daily limit exceeded',
        ]);

        $nonRetryable = CampaignSend::factory()->for($campaign)->for($subscriberC)->failed()->create([
            'bounce_reason' => 'Mailbox unavailable. The server response was: 5.1.1 user unknown',
        ]);

        (new ResumeFailedCampaignSendsJob($campaign->id))->handle(app(\App\Services\Newsletter\CampaignSendRetryService::class));

        $this->assertSame('queued', $retryableA->fresh()->status);
        $this->assertNull($retryableA->fresh()->failed_at);
        $this->assertNull($retryableA->fresh()->bounce_reason);

        $this->assertSame('queued', $retryableB->fresh()->status);
        $this->assertSame('failed', $nonRetryable->fresh()->status);
        $this->assertSame('sending', $campaign->fresh()->status);

        Queue::assertPushed(SendNewsletterEmailJob::class, 2);
        Queue::assertPushedOn('emails', SendNewsletterEmailJob::class);
    }
}
