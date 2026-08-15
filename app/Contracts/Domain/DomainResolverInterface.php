<?php

namespace App\Contracts\Domain;

interface DomainResolverInterface
{
    /**
     * Resolve the effective domain for a product and surface policy.
     */
    public function resolveProductDomain(string $productKey, string $surfacePolicy): ?string;

    /**
     * Resolve the effective default domain for an organisation and surface policy.
     */
    public function resolveOrganisationDomain(string $organisationKey, string $surfacePolicy): ?string;

    /**
     * Resolve inbound request context from a host and path.
     *
     * @return array<string, mixed>
     */
    public function resolveRequestContext(string $host, string $path = '/'): array;

    /**
     * Determine whether a domain configuration is verified.
     *
     * @param  array<string, mixed>  $domainConfig
     */
    public function isVerified(array $domainConfig): bool;

    /**
     * Determine whether a domain configuration is enabled.
     *
     * @param  array<string, mixed>  $domainConfig
     */
    public function isEnabled(array $domainConfig): bool;
}
