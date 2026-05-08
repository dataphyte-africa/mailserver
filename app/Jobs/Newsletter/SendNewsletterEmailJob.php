<?php

namespace App\Jobs\Newsletter;

use App\Mail\NewsletterMailable;
use App\Models\CampaignSend;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\SentMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a single newsletter email to one subscriber.
 *
 * Send-rate is throttled through a shared cache lock so queued jobs wait
 * in-process instead of repeatedly releasing back to the queue.
 * Runs on the `emails` queue.
 */
class SendNewsletterEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 5;
    public int $timeout = 60;
    public int $maxExceptions = 3;

    public function __construct(public readonly int $campaignSendId) {}

    /* ------------------------------------------------------------------ */

    public function handle(): void
    {
        $send = CampaignSend::with(['campaign', 'subscriber'])->find($this->campaignSendId);

        if (! $send) {
            Log::error("SendNewsletterEmailJob: CampaignSend {$this->campaignSendId} not found", [
                'attempt' => $this->attempts(),
            ]);

            if ($this->job !== null) {
                $this->fail(new \RuntimeException("CampaignSend {$this->campaignSendId} not found"));
            }

            return;
        }

        // Skip if already processed (idempotency)
        if (! in_array($send->status, ['queued', 'failed'])) {
            return;
        }

        $campaign   = $send->campaign;
        $subscriber = $send->subscriber;

        if (! $campaign || ! $subscriber) {
            $send->update(['status' => 'failed', 'bounce_reason' => 'Missing campaign or subscriber']);
            return;
        }

        try {
            $this->throttleSendRate();

            $mailable = new NewsletterMailable($campaign, $subscriber, (string) $send->id);

            $sentMessage = Mail::to($subscriber->email, $subscriber->full_name)
                ->send($mailable);

            // Extract Elastic Email transaction ID from the sent message headers
            $transactionId = $this->extractTransactionId($sentMessage);

            $send->update([
                'status'                          => 'sent',
                'sent_at'                         => now(),
                'elastic_email_transaction_id'    => $transactionId,
            ]);

        } catch (\Throwable $e) {
            Log::error("SendNewsletterEmailJob: send {$send->id} failed — {$e->getMessage()}");

            $send->update([
                'status'       => 'failed',
                'failed_at'    => now(),
                'bounce_reason' => substr($e->getMessage(), 0, 255),
            ]);

            // Re-throw so the queue worker can retry
            throw $e;
        }
    }

    /* ------------------------------------------------------------------ */

    public function backoff(): array
    {
        return [60, 120, 300]; // 1 min, 2 min, 5 min
    }

    public function failed(\Throwable $exception): void
    {
        $send = CampaignSend::find($this->campaignSendId);
        if ($send && $send->status !== 'bounced') {
            $send->update([
                'status'       => 'failed',
                'failed_at'    => now(),
                'bounce_reason' => substr($exception->getMessage(), 0, 255),
            ]);
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * Laravel's mailer returns an Illuminate\Mail\SentMessage wrapper when a
     * message is actually sent. The ElasticEmailTransport stores the transaction
     * ID on the underlying Symfony message headers.
     */
    private function extractTransactionId(mixed $sentMessage): ?string
    {
        try {
            if ($sentMessage instanceof SentMessage) {
                return $sentMessage->getOriginalMessage()
                    ->getHeaders()
                    ->get('X-ElasticEmail-TransactionId')
                    ?->getBodyAsString();
            }
        } catch (\Throwable) {
            // Non-critical — tracking still works via campaign_send ID
        }

        return null;
    }

    private function throttleSendRate(): void
    {
        $rate = max(1, (int) config('newsletter.send_rate', 50));
        $secondsPerSend = 60 / $rate;

        try {
            Cache::lock('newsletter-email-send-throttle', 30)->block(30, function () use ($secondsPerSend) {
                $cacheKey = 'newsletter-email-send-last-at';
                $lastSentAt = Cache::get($cacheKey);

                if (is_numeric($lastSentAt)) {
                    $waitSeconds = $secondsPerSend - (microtime(true) - (float) $lastSentAt);

                    if ($waitSeconds > 0) {
                        usleep((int) ceil($waitSeconds * 1_000_000));
                    }
                }

                Cache::put($cacheKey, microtime(true), now()->addMinutes(10));
            });
        } catch (\Throwable $e) {
            Log::warning('SendNewsletterEmailJob: shared send-rate throttle unavailable', [
                'campaign_send_id' => $this->campaignSendId,
                'message' => $e->getMessage(),
            ]);
        }
    }

}
