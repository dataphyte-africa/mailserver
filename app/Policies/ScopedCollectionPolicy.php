<?php

namespace App\Policies;

use App\Services\Platform\CpOperatorScope;
use Statamic\Facades\User;
use Statamic\Policies\CollectionPolicy;

class ScopedCollectionPolicy extends CollectionPolicy
{
    public function before($user)
    {
        return User::fromUser($user)->isSuper() ? true : null;
    }

    public function view($user, $collection)
    {
        if (! app(CpOperatorScope::class)->canCollection(User::fromUser($user), $collection->handle())) {
            return false;
        }

        return parent::view($user, $collection);
    }
}
