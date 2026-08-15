<?php

namespace App\Contracts\Authorization;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Statamic\Contracts\Auth\User as StatamicUser;

interface StatamicUserIdentityBridgeInterface
{
    public function resolve(?Authenticatable $operator): ?User;

    public function provision(StatamicUser $operator, ?string $name = null): User;
}
