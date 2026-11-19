<?php

namespace App\Services\Tor;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class PaystackAccountService
{
    /** @return array<int, array{code: string, name: string}> */
    public function banks(): array
    {
        return Cache::remember('tor.paystack.nigerian-banks', now()->addDay(), function (): array {
            $response = $this->client()->get('/bank', ['country' => 'nigeria', 'perPage' => 100]);

            if (! $response->successful() || ! $response->json('status')) {
                throw ValidationException::withMessages(['bank' => 'Bank choices are temporarily unavailable. Please try again shortly.']);
            }

            return collect($response->json('data', []))
                ->filter(fn (array $bank) => filled($bank['code'] ?? null) && filled($bank['name'] ?? null))
                ->map(fn (array $bank) => ['code' => (string) $bank['code'], 'name' => (string) $bank['name']])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();
        });
    }

    /** @return array{account_name: string} */
    public function resolve(string $bankCode, string $accountNumber): array
    {
        try {
            $response = $this->client()->get('/bank/resolve', [
                'bank_code' => $bankCode,
                'account_number' => $accountNumber,
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['bank_account' => 'Account verification is temporarily unavailable. Please try again.']);
        }

        $accountName = $response->json('data.account_name');
        $accountName = is_string($accountName) ? trim(preg_replace('/\s+/', ' ', $accountName) ?? '') : '';
        $nameCharacters = preg_replace('/[^\p{L}\p{N}]/u', '', $accountName) ?? '';

        if (! $response->successful() || ! $response->json('status') || mb_strlen($nameCharacters) < 3) {
            $message = trim((string) $response->json('message'));
            $fallback = 'We could not retrieve a complete account name. Check the selected bank and 10-digit account number, then try again.';

            throw ValidationException::withMessages(['bank_account' => $message !== '' ? $message : $fallback]);
        }

        return ['account_name' => $accountName];
    }

    public function isTestMode(): bool
    {
        return str_starts_with((string) config('services.paystack.secret_key'), 'sk_test_');
    }

    private function client()
    {
        $secret = (string) config('services.paystack.secret_key');
        if ($secret === '') {
            throw ValidationException::withMessages(['bank_account' => 'Account verification has not been configured yet.']);
        }

        return Http::baseUrl('https://api.paystack.co')
            ->withToken($secret)
            ->acceptJson()
            ->timeout(10);
    }
}
