<?php

namespace App\Policies;

use App\Services\Platform\CpOperatorScope;
use Statamic\Contracts\Entries\Collection as CollectionContract;
use Statamic\Facades\User;
use Statamic\Policies\EntryPolicy;

class ScopedEntryPolicy extends EntryPolicy
{
    public function before($user)
    {
        return User::fromUser($user)->isSuper() ? true : null;
    }

    public function view($user, $entry)
    {
        return $this->inScope($user, $entry) && parent::view($user, $entry);
    }

    public function edit($user, $entry)
    {
        return $this->inScope($user, $entry) && parent::edit($user, $entry);
    }

    public function create($user, $collection, $site = null)
    {
        $scope = app(CpOperatorScope::class);
        // Non-super operators use the product picker; a direct create URL must name a scoped blueprint.
        return $scope->canEntry(User::fromUser($user), $collection->handle(), request()->query('blueprint'))
            && parent::create($user, $collection, $site);
    }

    public function store($user, $collection, $site = null)
    {
        return app(CpOperatorScope::class)->canEntry(
            User::fromUser($user), $collection->handle(), request()->input('_blueprint'),
        ) && parent::create($user, $collection, $site);
    }

    public function publish($user, $entry)
    {
        if ($entry instanceof CollectionContract) {
            return app(CpOperatorScope::class)->canCollection(User::fromUser($user), $entry->handle())
                && parent::publish($user, $entry);
        }

        return $this->inScope($user, $entry) && parent::publish($user, $entry);
    }

    private function inScope($user, $entry): bool
    {
        return app(CpOperatorScope::class)->canEntry(
            User::fromUser($user), $entry->collectionHandle(), $entry->blueprint()?->handle(),
        );
    }
}
