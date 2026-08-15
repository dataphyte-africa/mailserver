<?php

namespace App\Support\Platform\Domain;

use App\Contracts\Domain\DomainResolverInterface;
use App\Models\Organisation;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DomainResolver implements DomainResolverInterface
{
    public function resolveProductDomain(string $productKey, string $surfacePolicy): ?string
    {
        $product = $this->findProduct($productKey);

        if (! $product) {
            return $this->fallbackPlatformDomainForUnknownProduct($surfacePolicy);
        }

        $surface = $this->surfaceConfig($surfacePolicy);
        $policy = $this->surfacePolicy($surfacePolicy);
        $productDomain = $this->normaliseHost($this->productSurfaceDomain($product, $surface));
        $organisationDomain = $this->normaliseHost($product->organisation?->default_domain);
        $platformDomain = $this->platformDomain();

        if ($policy === 'platform_only') {
            return $platformDomain;
        }

        if ($policy === 'organisation_fallback') {
            return $organisationDomain ?: $platformDomain;
        }

        if ($this->canUseProductDomain($product, $productDomain)) {
            return $productDomain;
        }

        if ($policy === 'product_required') {
            return null;
        }

        if ($organisationDomain) {
            return $organisationDomain;
        }

        if ($this->allowsPlatformFallback($product)) {
            return $platformDomain;
        }

        return null;
    }

    public function resolveOrganisationDomain(string $organisationKey, string $surfacePolicy): ?string
    {
        $organisation = $this->findOrganisation($organisationKey);
        $platformDomain = $this->platformDomain();
        $policy = $this->surfacePolicy($surfacePolicy);

        if ($policy === 'platform_only') {
            return $platformDomain;
        }

        if (! $organisation) {
            return $policy === 'product_required' ? null : $platformDomain;
        }

        $organisationDomain = $this->normaliseHost($organisation->default_domain);

        if ($organisationDomain) {
            return $organisationDomain;
        }

        if ($policy === 'product_required') {
            return null;
        }

        return $platformDomain;
    }

    public function resolveRequestContext(string $host, string $path = '/'): array
    {
        $normalisedHost = $this->normaliseHost($host) ?? '';
        $platformDomain = $this->platformDomain();

        if ($normalisedHost === '' || $normalisedHost === $platformDomain) {
            return $this->platformContext($normalisedHost, $path);
        }

        $product = $this->findProductByDomain($normalisedHost);

        if ($product) {
            return $this->productContext($product, $normalisedHost, $path);
        }

        $organisation = Organisation::query()
            ->whereRaw('lower(default_domain) = ?', [Str::lower($normalisedHost)])
            ->first();

        if ($organisation) {
            return $this->organisationContext($organisation, $normalisedHost, $path);
        }

        return $this->platformContext($normalisedHost, $path, [
            'matched' => false,
            'matched_surface' => null,
            'matched_domain_field' => null,
        ]);
    }

    public function isVerified(array $domainConfig): bool
    {
        $status = $domainConfig['status'] ?? $domainConfig['domain_status'] ?? null;

        if ($status !== null) {
            return $status === 'verified';
        }

        return (bool) ($domainConfig['domain_verified_at'] ?? false);
    }

    public function isEnabled(array $domainConfig): bool
    {
        if (array_key_exists('enabled', $domainConfig)) {
            return (bool) $domainConfig['enabled'];
        }

        if (array_key_exists('disabled', $domainConfig)) {
            return ! (bool) $domainConfig['disabled'];
        }

        return true;
    }

    protected function findProduct(string $productKey): ?Product
    {
        $query = Product::query()->with('organisation');

        return $this->findScopedModel($query, $productKey, [
            'slug',
            'primary_collection_handle',
        ]);
    }

    protected function findOrganisation(string $organisationKey): ?Organisation
    {
        return $this->findScopedModel(Organisation::query(), $organisationKey, [
            'slug',
        ]);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $stringColumns
     * @return TModel|null
     */
    protected function findScopedModel(Builder $query, string $key, array $stringColumns): ?Model
    {
        $trimmed = trim($key);

        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            /** @var TModel|null $model */
            $model = $query->find((int) $trimmed);

            return $model;
        }

        $lowered = Str::lower($trimmed);

        foreach ($stringColumns as $column) {
            $candidate = (clone $query)
                ->whereRaw("lower({$column}) = ?", [$lowered])
                ->first();

            if ($candidate) {
                return $candidate;
            }
        }

        return null;
    }

    protected function fallbackPlatformDomainForUnknownProduct(string $surfacePolicy): ?string
    {
        $policy = $this->surfacePolicy($surfacePolicy);

        if ($policy === 'product_required') {
            return null;
        }

        return $this->platformDomain();
    }

    protected function surfacePolicy(string $surfaceOrPolicy): string
    {
        $surface = $this->surfaceConfig($surfaceOrPolicy);
        $policy = $surface['policy'] ?? null;

        if (is_string($policy) && in_array($policy, $this->availablePolicies(), true)) {
            return $policy;
        }

        if (in_array($surfaceOrPolicy, $this->availablePolicies(), true)) {
            return $surfaceOrPolicy;
        }

        return (string) config('platform.domain.default_surface_policy', 'product_preferred');
    }

    protected function surfaceConfig(string $surfaceOrPolicy): array
    {
        $surface = config("platform.domain.surfaces.{$surfaceOrPolicy}");

        return is_array($surface) ? $surface : [];
    }

    /**
     * @return array<int, string>
     */
    protected function availablePolicies(): array
    {
        $policies = config('platform.domain.surface_policies', []);

        return is_array($policies) ? array_values($policies) : [];
    }

    protected function productSurfaceDomain(Product $product, array $surface): ?string
    {
        $field = $surface['product_domain_field'] ?? 'public_domain';

        if (! is_string($field) || $field === '') {
            $field = 'public_domain';
        }

        $value = $product->getAttribute($field);

        return is_string($value) ? $value : null;
    }

    protected function canUseProductDomain(Product $product, ?string $domain): bool
    {
        return $domain !== null
            && $domain !== ''
            && $this->isEnabled($product->only([
                'domain_status',
                'domain_verified_at',
                'fallback_to_platform_domain',
            ]))
            && $this->isVerified($product->only([
                'domain_status',
                'domain_verified_at',
            ]));
    }

    protected function allowsPlatformFallback(Product $product): bool
    {
        return (bool) ($product->fallback_to_platform_domain ?? true);
    }

    protected function platformDomain(): ?string
    {
        return $this->normaliseHost((string) config('platform.domain.platform_domain', ''));
    }

    protected function normaliseHost(?string $host): ?string
    {
        if (! is_string($host)) {
            return null;
        }

        $trimmed = trim($host);

        if ($trimmed === '') {
            return null;
        }

        $parsed = parse_url(Str::contains($trimmed, '://') ? $trimmed : "https://{$trimmed}", PHP_URL_HOST);
        $candidate = is_string($parsed) ? $parsed : $trimmed;

        return Str::lower(rtrim($candidate, '/'));
    }

    protected function findProductByDomain(string $host): ?Product
    {
        return Product::query()
            ->with('organisation')
            ->where(function (Builder $query) use ($host): void {
                $query->whereRaw('lower(public_domain) = ?', [Str::lower($host)])
                    ->orWhereRaw('lower(forms_domain) = ?', [Str::lower($host)]);
            })
            ->first();
    }

    protected function productContext(Product $product, string $host, string $path): array
    {
        $matchedField = $this->matchedProductDomainField($product, $host);

        return [
            'scope_type' => 'product',
            'matched' => true,
            'host' => $host,
            'path' => $path,
            'platform_domain' => $this->platformDomain(),
            'organisation_id' => $product->organisation_id,
            'organisation_slug' => $product->organisation?->slug,
            'product_id' => $product->getKey(),
            'product_slug' => $product->slug,
            'product_primary_collection_handle' => $product->primary_collection_handle,
            'matched_domain_field' => $matchedField,
            'matched_surface' => $matchedField === 'forms_domain' ? 'form_page' : 'landing_page',
            'resolved_domain' => $host,
            'fallback_domain' => $this->resolveProductDomain((string) $product->getKey(), 'landing_page'),
            'domain_status' => $product->domain_status,
            'domain_verified' => $this->isVerified($product->only(['domain_status', 'domain_verified_at'])),
        ];
    }

    protected function organisationContext(Organisation $organisation, string $host, string $path): array
    {
        return [
            'scope_type' => 'organisation',
            'matched' => true,
            'host' => $host,
            'path' => $path,
            'platform_domain' => $this->platformDomain(),
            'organisation_id' => $organisation->getKey(),
            'organisation_slug' => $organisation->slug,
            'product_id' => null,
            'product_slug' => null,
            'product_primary_collection_handle' => null,
            'matched_domain_field' => 'default_domain',
            'matched_surface' => 'organisation_fallback',
            'resolved_domain' => $host,
            'fallback_domain' => $this->platformDomain(),
        ];
    }

    protected function platformContext(string $host, string $path, array $overrides = []): array
    {
        return array_merge([
            'scope_type' => 'platform',
            'matched' => true,
            'host' => $host,
            'path' => $path,
            'platform_domain' => $this->platformDomain(),
            'organisation_id' => null,
            'organisation_slug' => null,
            'product_id' => null,
            'product_slug' => null,
            'product_primary_collection_handle' => null,
            'matched_domain_field' => 'platform_domain',
            'matched_surface' => 'platform_only',
            'resolved_domain' => $this->platformDomain(),
            'fallback_domain' => $this->platformDomain(),
        ], $overrides);
    }

    protected function matchedProductDomainField(Product $product, string $host): string
    {
        if ($this->normaliseHost($product->forms_domain) === $host) {
            return 'forms_domain';
        }

        return 'public_domain';
    }
}
