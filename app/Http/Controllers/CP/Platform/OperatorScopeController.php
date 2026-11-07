<?php

namespace App\Http\Controllers\CP\Platform;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Organisation;
use App\Models\OrganisationUserScope;
use App\Models\Product;
use App\Services\Platform\CpOperatorScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Statamic\Facades\User as StatamicUser;

class OperatorScopeController
{
    public function index(Request $request)
    {
        abort_unless(app(CpOperatorScope::class)->isSuper($request->user()), 403);

        $users = StatamicUser::all()->filter(fn ($user) => ! $user->isSuper())->sortBy('email');
        $selectedUserId = $request->query('user');
        $selectedUser = $selectedUserId
            ? $users->first(fn ($user) => $user->id() === $selectedUserId)
            : $users->first();
        abort_if($selectedUserId && ! $selectedUser, 404);

        $identity = $selectedUser
            ? \App\Models\User::query()->where('statamic_user_id', $selectedUser->id())
                ->with('organisationScopes')->first()
            : null;

        return view('platform.cp.operator-scopes.index', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'identity' => $identity,
            'organisations' => Organisation::query()->where('status', 'active')->orderBy('name')->get(),
            'products' => Product::query()->with('organisation')->where('status', 'active')
                ->whereHas('organisation', fn ($q) => $q->where('status', 'active'))->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, string $userId, StatamicUserIdentityBridgeInterface $identity): RedirectResponse
    {
        abort_unless(app(CpOperatorScope::class)->isSuper($request->user()), 403);

        $statamicUser = StatamicUser::find($userId);
        abort_unless($statamicUser && ! $statamicUser->isSuper(), 404);

        $data = $request->validate([
            'organisation_ids' => ['array'],
            'organisation_ids.*' => ['integer', 'distinct', Rule::exists('organisations', 'id')->where('status', 'active')],
        ]);

        $organisationIds = array_map('intval', $data['organisation_ids'] ?? []);

        DB::transaction(function () use ($request, $identity, $statamicUser, $organisationIds): void {
            $target = $identity->provision($statamicUser);
            $granter = $identity->resolve($request->user());

            OrganisationUserScope::query()->where('user_id', $target->id)->whereNotIn('organisation_id', $organisationIds)
                ->update(['status' => 'inactive']);

            foreach ($organisationIds as $organisationId) {
                OrganisationUserScope::query()->updateOrCreate(
                    ['user_id' => $target->id, 'organisation_id' => $organisationId],
                    ['scope_role' => 'organisation_operator', 'status' => 'active', 'granted_by' => $granter?->id],
                );
            }
        });

        return redirect(cp_route('operator-scopes.index', ['user' => $statamicUser->id()]))
            ->with('success', 'Operator access updated.');
    }
}
