<?php

namespace App\Support\Platform\Authorization;

use App\Contracts\Authorization\ScopeResolverInterface;
use App\Models\User;

trait ResolvesAuthorizationScope
{
    protected function userHasOrganisationScope(User $user, int $organisationId, array $scopeRoles = []): bool
    {
        return app(ScopeResolverInterface::class)->hasOrganisationScope($user, $organisationId, $scopeRoles);
    }

    protected function userHasProductScope(User $user, int $productId, array $scopeRoles = []): bool
    {
        return app(ScopeResolverInterface::class)->hasProductScope($user, $productId, $scopeRoles);
    }
}
