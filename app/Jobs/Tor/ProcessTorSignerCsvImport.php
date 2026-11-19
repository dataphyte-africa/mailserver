<?php

namespace App\Jobs\Tor;

use App\Mail\TorInvitationMail;
use App\Models\TorFlow;
use App\Models\TorInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProcessTorSignerCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    private const BATCH_SIZE = 50;

    public function __construct(
        public readonly int $flowId,
        public readonly string $importId,
        public readonly string $path,
        public readonly array $headers,
        public readonly int $offset,
    ) {}

    public static function cacheKey(string $importId): string
    {
        return "tor-signer-import:{$importId}";
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(): void
    {
        $flow = TorFlow::query()->with('organisation')->find($this->flowId);
        if (! $flow || ! $flow->canInviteSigners()) {
            $this->finishWithError('The TOR is no longer accepting signer imports.');

            return;
        }

        $handle = fopen(Storage::disk('local')->path($this->path), 'rb');
        if ($handle === false) {
            throw new \RuntimeException('The stored signer CSV could not be opened.');
        }

        $result = Cache::get(self::cacheKey($this->importId), []);
        $result = array_replace([
            'status' => 'processing',
            'processed' => 0,
            'imported' => 0,
            'invitations_queued' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ], $result);
        $result['status'] = 'processing';
        $processed = 0;

        try {
            fseek($handle, $this->offset);
            while ($processed < self::BATCH_SIZE && ($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                $processed++;
                $result['processed']++;
                $rowNumber = $result['processed'] + 1;
                $row = array_combine($this->headers, array_pad(array_slice($values, 0, count($this->headers)), count($this->headers), null));
                $name = trim((string) ($row['full_name'] ?? ''));
                $email = strtolower(trim((string) ($row['email'] ?? '')));

                if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->error($result, "Row {$rowNumber}: a full_name and valid email are required.");
                    $result['failed']++;

                    continue;
                }

                try {
                    $invitation = TorInvitation::query()->firstOrNew(['tor_flow_id' => $flow->id, 'email' => $email]);
                    if (! $invitation->exists) {
                        $invitation->token = Str::random(80);
                    }
                    if (! $invitation->isComplete()) {
                        $invitation->fill([
                            'expected_name' => $name,
                            'state' => $this->nullableValue($row['state'] ?? null),
                            'position' => $this->nullableValue($row['position'] ?? null),
                            'phone_number' => $this->nullableValue($row['phone_number'] ?? null),
                        ]);
                    }
                    $invitation->save();

                    if ($invitation->isComplete() || $invitation->status === 'revoked') {
                        $result['skipped']++;

                        continue;
                    }

                    Mail::to($invitation->email)->queue((new TorInvitationMail($invitation->load('flow.organisation')))->onQueue('emails'));
                    $invitation->update(['status' => 'sent', 'sent_at' => now()]);
                    $result['imported']++;
                    $result['invitations_queued']++;
                } catch (Throwable $exception) {
                    report($exception);
                    $result['failed']++;
                    $this->error($result, "Row {$rowNumber}: this signer could not be imported.");
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
            self::dispatch($this->flowId, $this->importId, $this->path, $this->headers, $nextOffset);
        }
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
        $this->finishWithError('The import stopped before it could finish. Please try again.');
    }

    private function finishWithError(string $message): void
    {
        $result = Cache::get(self::cacheKey($this->importId), []);
        $result['status'] = 'failed';
        $this->error($result, $message);
        Cache::put(self::cacheKey($this->importId), $result, now()->addDays(7));
    }

    private function nullableValue(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function error(array &$result, string $message): void
    {
        $result['errors'] ??= [];
        if (count($result['errors']) < 25) {
            $result['errors'][] = $message;
        }
    }
}
