<?php

namespace App\Http\Controllers\CP\Platform;

use App\Services\Platform\CpOperatorScope;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ActiveOrganisationController
{
    public function index(Request $request, CpOperatorScope $scope): View
    {
        abort_if($scope->isSuper($request->user()), 404);

        $organisations = $scope->availableOrganisations($request->user());
        $activeOrganisationId = $scope->activeOrganisationId($request->user());

        return view('platform.cp.active-organisation.index', compact('organisations', 'activeOrganisationId'));
    }

    public function update(Request $request, CpOperatorScope $scope): RedirectResponse
    {
        abort_if($scope->isSuper($request->user()), 404);

        $validated = $request->validate(['organisation_id' => ['required', 'integer']]);
        $organisationId = (int) $validated['organisation_id'];
        abort_unless($scope->availableOrganisations($request->user())->contains('id', $organisationId), 403);

        $request->session()->put(CpOperatorScope::ACTIVE_ORGANISATION_SESSION_KEY, $organisationId);

        return redirect(cp_route('dashboard'));
    }
}
