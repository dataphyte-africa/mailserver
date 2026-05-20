<?php

namespace App\Console\Commands\Newsletter;

use App\Models\Subscriber;
use App\Services\Newsletter\SubscriberEngagementService;
use Illuminate\Console\Command;

class RecomputeSubscriberEngagement extends Command
{
    protected $signature = 'newsletter:recompute-subscriber-engagement
        {--subscriber= : Recompute a single subscriber ID}
        {--chunk=250 : Chunk size for batch recompute}';

    protected $description = 'Recompute subscriber engagement score, rating, and last engaged timestamp.';

    public function handle(SubscriberEngagementService $service): int
    {
        $subscriberId = $this->option('subscriber');
        $chunkSize = max((int) $this->option('chunk'), 1);

        if ($subscriberId) {
            $subscriber = Subscriber::find($subscriberId);

            if (! $subscriber) {
                $this->error("Subscriber {$subscriberId} not found.");
                return self::FAILURE;
            }

            $service->persist($subscriber);
            $this->info("Recomputed engagement for subscriber #{$subscriber->id} ({$subscriber->email}).");

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar((int) Subscriber::count());
        $bar->start();

        Subscriber::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($subscribers) use ($service, $bar) {
                foreach ($subscribers as $subscriber) {
                    $service->persist($subscriber);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Subscriber engagement recompute complete.');

        return self::SUCCESS;
    }
}
