<?php

namespace App\Services\Foundation;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ElectionLocationService
{
    private const STATES_TTL_MINUTES = 10080;
    private const LGAS_TTL_MINUTES = 10080;
    private const WARDS_TTL_MINUTES = 10080;

    public function states(): array
    {
        return $this->cached(
            key: 'states',
            ttlMinutes: self::STATES_TTL_MINUTES,
            loader: fn () => collect($this->request('api/v1/locations/states'))
                ->map(fn (array $row) => [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
        );
    }

    public function lgas(int $stateId): array
    {
        return $this->cached(
            key: "states.{$stateId}.lgas",
            ttlMinutes: self::LGAS_TTL_MINUTES,
            loader: fn () => collect($this->request("api/v1/locations/states/{$stateId}/lgas"))
                ->map(fn (array $row) => [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                    'state_id' => (int) $row['state_id'],
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
        );
    }

    public function wards(int $lgaId): array
    {
        return $this->cached(
            key: "lgas.{$lgaId}.wards",
            ttlMinutes: self::WARDS_TTL_MINUTES,
            loader: fn () => collect($this->request("api/v1/locations/lgas/{$lgaId}/registration-areas"))
                ->map(fn (array $row) => [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                    'lga_id' => (int) $row['lga_id'],
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
        );
    }

    public function findStateByName(string $name): ?array
    {
        return collect($this->states()['data'])
            ->first(fn (array $state) => Str::lower($state['name']) === Str::lower($name));
    }

    private function cached(string $key, int $ttlMinutes, callable $loader): array
    {
        $cacheKey = "foundation.election_locations.{$key}";
        $backupKey = "{$cacheKey}.backup";

        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['source' => 'cache']);
        }

        try {
            $data = $loader();
            $payload = [
                'data' => $data,
                'cached_at' => now(config('app.timezone'))->toIso8601String(),
            ];

            Cache::put($cacheKey, $payload, now()->addMinutes($ttlMinutes));
            Cache::forever($backupKey, $payload);

            return array_merge($payload, ['source' => 'origin']);
        } catch (\Throwable $exception) {
            if ($backup = Cache::get($backupKey)) {
                return array_merge($backup, ['source' => 'stale']);
            }

            throw $exception;
        }
    }

    private function request(string $path): array
    {
        $baseUrl = rtrim((string) config('services.dataphyte_election.base_url'), '/');
        $timeout = max(3, (int) config('services.dataphyte_election.timeout', 10));
        $token = trim((string) config('services.dataphyte_election.token'));

        $request = Http::acceptJson()
            ->timeout($timeout)
            ->retry(2, 300, throw: false);

        if ($token !== '') {
            $request = $request->withToken($token);
        }

        $response = $request->get("{$baseUrl}/{$path}");

        if (! $response->successful()) {
            throw new RequestException($response);
        }

        return Arr::wrap($response->json('data'));
    }
}
