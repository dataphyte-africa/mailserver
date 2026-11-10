<?php

namespace App\Jobs\Newsletter;

use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\GroupEngagementService;
use App\Services\Newsletter\SubscriberEngagementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessSubscriberCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 75;

    public int $tries = 3;

    private const BATCH_SIZE = 50;

    public function __construct(
        public readonly string $importId,
        public readonly string $path,
        public readonly array $headers,
        public readonly int $offset,
        public readonly array $defaultSubGroupIds,
        public readonly array $allowedSubGroupIds,
    ) {
        $this->onQueue('default');
    }

    public static function cacheKey(string $importId): string
    {
        return "subscriber-csv-import:{$importId}";
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(SubscriberEngagementService $engagement, GroupEngagementService $groupEngagement): void
    {
        $handle = fopen(Storage::disk('local')->path($this->path), 'rb');
        if ($handle === false) {
            throw new \RuntimeException('The stored subscriber import file could not be opened.');
        }

        try {
            fseek($handle, $this->offset);
            $result = Cache::get(self::cacheKey($this->importId));
            if (! is_array($result)) {
                throw new \RuntimeException('The subscriber import progress record has expired.');
            }

            $result['status'] = 'processing';
            $allowed = SubscriberSubGroup::query()
                ->whereIn('id', $this->allowedSubGroupIds)
                ->where('purpose', 'preference')
                ->whereNull('archived_at')
                ->whereHas('group', fn ($query) => $query->whereNull('archived_at'))
                ->get()
                ->keyBy('id');
            $slugs = $allowed->groupBy('slug');
            $processed = 0;

            while ($processed < self::BATCH_SIZE && ($data = fgetcsv($handle)) !== false) {
                $processed++;
                $result['processed']++;
                $row = $result['processed'] + 1;
                $rowData = array_combine($this->headers, array_slice(array_pad($data, count($this->headers), null), 0, count($this->headers)));
                $email = strtolower(trim((string) ($rowData['email'] ?? '')));

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->skip($result, "Row {$row}: Invalid email");

                    continue;
                }

                $subGroupIds = [];
                foreach (array_filter(array_map('trim', explode(',', (string) ($rowData['sub_groups'] ?? '')))) as $slug) {
                    $matches = $slugs->get($slug);
                    if ($matches?->count() === 1) {
                        $subGroupIds[] = $matches->first()->id;
                    } else {
                        $this->error($result, "Row {$row}: Unknown or ambiguous sub-group slug '{$slug}'");
                    }
                }
                $subGroupIds = array_values(array_unique(array_merge($subGroupIds, array_intersect($this->defaultSubGroupIds, $allowed->keys()->all()))));
                if ($subGroupIds === []) {
                    $this->skip($result, "Row {$row}: No valid sub-groups");

                    continue;
                }

                try {
                    DB::transaction(function () use ($email, $rowData, $subGroupIds, $engagement, $groupEngagement): void {
                        $subscriber = Subscriber::firstOrCreate(
                            ['email' => $email],
                            [
                                'first_name' => trim((string) ($rowData['first_name'] ?? '')),
                                'last_name' => trim((string) ($rowData['last_name'] ?? '')),
                                'status' => 'active',
                                'confirmation_token' => Str::uuid()->toString(),
                            ]
                        );
                        if (! $subscriber->confirmation_token) {
                            $subscriber->update(['confirmation_token' => Str::uuid()->toString()]);
                        }
                        $existing = $subscriber->allSubGroups()->pluck('subscriber_sub_groups.id')->all();
                        $toAttach = array_diff($subGroupIds, $existing);
                        if ($toAttach !== []) {
                            $subscriber->subGroups()->attach($toAttach, ['subscribed_at' => now()]);
                        }
                        $engagement->persist($subscriber);
                        $groupEngagement->syncForSubscriber($subscriber);
                    });
                    $result['imported']++;
                } catch (Throwable $exception) {
                    report($exception);
                    $this->skip($result, "Row {$row}: Could not import this subscriber");
                }
            }

            $nextOffset = ftell($handle);
            $complete = feof($handle);
            $result['status'] = $complete ? 'complete' : 'processing';
            Cache::put(self::cacheKey($this->importId), $result, now()->addDays(7));
        } finally {
            fclose($handle);
        }

        if ($complete) {
            Storage::disk('local')->delete($this->path);
        } else {
            self::dispatch($this->importId, $this->path, $this->headers, $nextOffset, $this->defaultSubGroupIds, $this->allowedSubGroupIds);
        }
    }

    public function failed(Throwable $exception): void
    {
        $result = Cache::get(self::cacheKey($this->importId));
        if (is_array($result)) {
            $result['status'] = 'failed';
            $this->error($result, 'Import stopped before completion. Contact an administrator with this import ID.');
            Cache::put(self::cacheKey($this->importId), $result, now()->addDays(7));
        }
        report($exception);
    }

    private function skip(array &$result, string $message): void
    {
        $result['skipped']++;
        $this->error($result, $message);
    }

    private function error(array &$result, string $message): void
    {
        if (count($result['errors']) < 25) {
            $result['errors'][] = $message;
        }
    }
}
