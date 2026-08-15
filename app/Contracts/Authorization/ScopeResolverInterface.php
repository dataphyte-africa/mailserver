<?php

namespace App\Contracts\Authorization;

use App\Models\OrganisationUserScope;
use App\Models\ProductUserScope;
use App\Models\User;

interface ScopeResolverInterface
{
    /**
     * @return array<int, int>
     */
    public function organisationIds(User $user, array $scopeRoles = []): array;

    /**
     * @return array<int, int>
     */
    public function productIds(User $user, array $scopeRoles = []): array;

    public function hasOrganisationScope(User $user, int $organisationId, array $scopeRoles = []): bool;

    public function hasProductScope(User $user, int $productId, array $scopeRoles = []): bool;

    public function organisationScope(User $user, int $organisationId): ?OrganisationUserScope;

    public function productScope(User $user, int $productId): ?ProductUserScope;
}
