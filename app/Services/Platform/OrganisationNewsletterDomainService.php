<?php

namespace App\Services\Platform;

use App\Models\Organisation;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganisationNewsletterDomainService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Organisation $organisation, array $attributes): Organisation
    {
        $sourceDomain = $this->normaliseHost($attributes['source_domain'] ?? null);
        $subdomain = $this->normaliseSubdomain($attributes['newsletter_subdomain'] ?? null);
        $recordType = $this->recordType($attributes['newsletter_dns_record_type'] ?? null);
        $expectedValue = $this->normaliseExpectedValue($attributes['newsletter_dns_expected_value'] ?? null);

        $organisation->forceFill([
            'source_domain' => $sourceDomain,
            'newsletter_subdomain' => $subdomain,
            'newsletter_domain' => $sourceDomain ? "{$subdomain}.{$sourceDomain}" : null,
            'newsletter_domain_status' => $sourceDomain ? 'pending_verification' : 'unconfigured',
            'newsletter_domain_verified_at' => null,
            'newsletter_dns_record_type' => $recordType,
            'newsletter_dns_expected_value' => $expectedValue ?: $this->defaultExpectedValue(),
        ])->save();

        return $organisation->refresh();
    }

    /**
     * @return array<string, string|null>
     */
    public function dnsRecord(Organisation $organisation): array
    {
        return [
            'type' => $organisation->newsletter_dns_record_type ?: $this->defaultRecordType(),
            'name' => $organisation->newsletter_subdomain ?: $this->defaultSubdomain(),
            'host' => $organisation->newsletter_domain,
            'value' => $organisation->newsletter_dns_expected_value ?: $this->defaultExpectedValue(),
            'ttl' => 'Auto / 300',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function allowedOrigins(Organisation $organisation): array
    {
        return array_values(array_unique(array_filter([
            $this->origin($organisation->source_domain),
            $this->origin($organisation->source_domain ? 'www.'.$organisation->source_domain : null),
            $this->origin($organisation->newsletter_domain),
        ])));
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(Organisation $organisation): array
    {
        $record = $this->dnsRecord($organisation);
        $host = $record['host'];
        $expected = $record['value'];

        if (! $host || ! $expected) {
            return $this->verificationResult(false, [], 'DNS record is not configured yet.');
        }

        $actual = $record['type'] === 'CNAME'
            ? $this->cnameRecords($host)
            : $this->aRecords($host);
        $matched = in_array(Str::lower($expected), array_map(fn (string $value): string => Str::lower(rtrim($value, '.')), $actual), true);

        $organisation->forceFill([
            'newsletter_domain_status' => $matched ? 'verified' : 'pending_verification',
            'newsletter_domain_verified_at' => $matched ? now() : null,
        ])->save();

        return $this->verificationResult($matched, $actual, $matched ? 'DNS record verified.' : 'DNS record has not resolved to the expected value yet.');
    }

    public function normaliseHost(mixed $host): ?string
    {
        if (! is_string($host)) {
            return null;
        }

        $trimmed = trim($host);

        if ($trimmed === '') {
            return null;
        }

        $parsed = parse_url(Str::contains($trimmed, '://') ? $trimmed : "https://{$trimmed}", PHP_URL_HOST);
        $candidate = Str::lower(rtrim((string) ($parsed ?: $trimmed), '/'));

        if (Str::startsWith($candidate, 'www.')) {
            $candidate = Str::after($candidate, 'www.');
        }

        if (! preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $candidate)) {
            throw ValidationException::withMessages([
                'source_domain' => ['Enter a valid root domain, for example dataphyte.org.'],
            ]);
        }

        return $candidate;
    }

    public function normaliseSubdomain(mixed $subdomain): string
    {
        $candidate = Str::lower(trim((string) ($subdomain ?: $this->defaultSubdomain())));

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/', $candidate)) {
            throw ValidationException::withMessages([
                'newsletter_subdomain' => ['Enter a valid DNS subdomain, for example nl.'],
            ]);
        }

        return $candidate;
    }

    protected function recordType(mixed $type): string
    {
        $candidate = Str::upper(trim((string) ($type ?: $this->defaultRecordType())));

        return in_array($candidate, ['A', 'CNAME'], true) ? $candidate : $this->defaultRecordType();
    }

    protected function normaliseExpectedValue(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : rtrim(Str::lower($trimmed), '.');
    }

    protected function origin(?string $host): ?string
    {
        return $host ? sprintf('%s://%s', config('platform.domain.platform_scheme', 'https'), $host) : null;
    }

    /**
     * @return array<int, string>
     */
    protected function aRecords(string $host): array
    {
        return array_values(array_filter(gethostbynamel($host) ?: []));
    }

    /**
     * @return array<int, string>
     */
    protected function cnameRecords(string $host): array
    {
        $records = dns_get_record($host, DNS_CNAME);

        if (! is_array($records)) {
            return [];
        }

        return collect($records)
            ->pluck('target')
            ->filter()
            ->map(fn (string $target): string => rtrim($target, '.'))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $actual
     * @return array<string, mixed>
     */
    protected function verificationResult(bool $matched, array $actual, string $message): array
    {
        return [
            'matched' => $matched,
            'actual' => $actual,
            'message' => $message,
        ];
    }

    protected function defaultSubdomain(): string
    {
        return (string) config('platform.domain.newsletter_subdomain', 'nl');
    }

    protected function defaultRecordType(): string
    {
        return (string) config('platform.domain.newsletter_dns_record_type', 'A');
    }

    protected function defaultExpectedValue(): ?string
    {
        $value = config('platform.domain.newsletter_dns_target');

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
