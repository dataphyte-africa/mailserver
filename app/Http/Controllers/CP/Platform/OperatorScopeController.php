<?php

namespace App\Http\Controllers\CP\Platform;

use App\Contracts\Authorization\StatamicUserIdentityBridgeInterface;
use App\Models\Organisation;
use App\Models\OrganisationUserScope;
use App\Models\Product;
use App\Models\ProductUserScope;
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
        $identities = \App\Models\User::query()->whereIn('statamic_user_id', $users->map(fn ($user) => $user->id())->all())
            ->with(['organisationScopes', 'productScopes'])->get()->keyBy('statamic_user_id');

        return view('platform.cp.operator-scopes.index', [
            'users' => $users,
            'identities' => $identities,
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
            'product_ids' => ['array'],
            'product_ids.*' => ['integer', 'distinct', Rule::exists('products', 'id')->where('status', 'active')],
        ]);

        $organisationIds = array_map('intval', $data['organisation_ids'] ?? []);
        $productIds = array_map('intval', $data['product_ids'] ?? []);
        $products = Product::query()->whereKey($productIds)
            ->whereHas('organisation', fn ($q) => $q->where('status', 'active'))->get(['id', 'organisation_id']);
        abort_unless($products->count() === count($productIds), 422);
        $productIds = $products->reject(fn (Product $product) => in_array((int) $product->organisation_id, $organisationIds, true))
            ->pluck('id')->all();

        DB::transaction(function () use ($request, $identity, $statamicUser, $organisationIds, $productIds): void {
            $target = $identity->provision($statamicUser);
            $granter = $identity->resolve($request->user());

            OrganisationUserScope::query()->where('user_id', $target->id)->whereNotIn('organisation_id', $organisationIds)
                ->update(['status' => 'inactive']);
            ProductUserScope::query()->where('user_id', $target->id)->whereNotIn('product_id', $productIds)
                ->update(['status' => 'inactive']);

            foreach ($organisationIds as $organisationId) {
                OrganisationUserScope::query()->updateOrCreate(
                    ['user_id' => $target->id, 'organisation_id' => $organisationId],
                    ['scope_role' => 'organisation_operator', 'status' => 'active', 'granted_by' => $granter?->id],
                );
            }
            foreach ($productIds as $productId) {
                ProductUserScope::query()->updateOrCreate(
                    ['user_id' => $target->id, 'product_id' => $productId],
                    ['scope_role' => 'product_operator', 'status' => 'active', 'granted_by' => $granter?->id],
                );
            }
        });

        return redirect()->back()->with('success', 'Operator access updated.');
    }
}
