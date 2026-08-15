<?php

namespace App\Support\Platform\Authorization;

use App\Contracts\Authorization\ScopeResolverInterface;
use App\Models\OrganisationUserScope;
use App\Models\ProductUserScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ScopeResolver implements ScopeResolverInterface
{
    public function __construct(
        private readonly ?string $configuredActiveStatus = null,
    ) {}

    public function organisationIds(User $user, array $scopeRoles = []): array
    {
        return $this->activeOrganisationScopeQuery($user, $scopeRoles)
            ->pluck('organisation_id')
            ->map(fn (mixed $id) => (int) $id)
            ->all();
    }

    public function productIds(User $user, array $scopeRoles = []): array
    {
        return $this->activeProductScopeQuery($user, $scopeRoles)
            ->pluck('product_id')
            ->map(fn (mixed $id) => (int) $id)
            ->all();
    }

    public function hasOrganisationScope(User $user, int $organisationId, array $scopeRoles = []): bool
    {
        return $this->activeOrganisationScopeQuery($user, $scopeRoles)
            ->where('organisation_id', $organisationId)
            ->exists();
    }

    public function hasProductScope(User $user, int $productId, array $scopeRoles = []): bool
    {
        return $this->activeProductScopeQuery($user, $scopeRoles)
            ->where('product_id', $productId)
            ->exists();
    }

    public function organisationScope(User $user, int $organisationId): ?OrganisationUserScope
    {
        /** @var OrganisationUserScope|null $scope */
        $scope = $this->activeOrganisationScopeQuery($user)
            ->where('organisation_id', $organisationId)
            ->first();

        return $scope;
    }

    public function productScope(User $user, int $productId): ?ProductUserScope
    {
        /** @var ProductUserScope|null $scope */
        $scope = $this->activeProductScopeQuery($user)
            ->where('product_id', $productId)
            ->first();

        return $scope;
    }

    /**
     * @return Builder<OrganisationUserScope>
     */
    protected function activeOrganisationScopeQuery(User $user, array $scopeRoles = []): Builder
    {
        $query = $user->organisationScopes()->getQuery()
            ->where('user_id', $user->getKey())
            ->where('status', $this->activeStatus());

        return $this->applyScopeRoles($query, $scopeRoles);
    }

    /**
     * @return Builder<ProductUserScope>
     */
    protected function activeProductScopeQuery(User $user, array $scopeRoles = []): Builder
    {
        $query = $user->productScopes()->getQuery()
            ->where('user_id', $user->getKey())
            ->where('status', $this->activeStatus());

        return $this->applyScopeRoles($query, $scopeRoles);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyScopeRoles(Builder $query, array $scopeRoles): Builder
    {
        $roles = array_values(array_filter($scopeRoles, fn (mixed $role) => is_string($role) && $role !== ''));

        if ($roles === []) {
            return $query;
        }

        return $query->whereIn('scope_role', $roles);
    }

    protected function activeStatus(): string
    {
        return $this->configuredActiveStatus
            ?? (string) config('platform.authorization.scope_statuses.active', 'active');
    }
}
