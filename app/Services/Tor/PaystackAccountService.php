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
        if (! $response->successful() || ! $response->json('status') || ! is_string($accountName) || $accountName === '') {
            throw ValidationException::withMessages(['bank_account' => 'We could not verify that bank account. Check the bank and account number, then try again.']);
        }

        return ['account_name' => $accountName];
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
