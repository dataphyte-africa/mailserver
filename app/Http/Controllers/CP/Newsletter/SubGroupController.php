<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\ScopedSubscriberGroupDeletionService;
use App\Services\Newsletter\ScopedSubscriberGroupProductSelector;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubGroupController extends Controller
{
    public function store(
        Request $request,
        SubscriberGroup $group,
        ScopedSubscriberGroupProductSelector $products,
    ) {
        abort_if(
            $products->resolveGroup($request->user(), $group) === null,
            403,
            'Audience group is outside your active product scope.'
        );

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->subGroups()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.edit', $group)
            ->with('success', 'Sub-group added.');
    }

    public function update(
        Request $request,
        SubscriberGroup $group,
        SubscriberSubGroup $subGroup,
        ScopedSubscriberGroupProductSelector $products,
    ) {
        abort_if(
            $products->resolveSubGroup($request->user(), $group, $subGroup) === null,
            403,
            'Subgroup is outside the selected audience product scope.'
        );

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $subGroup->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.edit', $group)
            ->with('success', 'Sub-group updated.');
    }

    public function destroy(
        Request $request,
        SubscriberGroup $group,
        SubscriberSubGroup $subGroup,
        ScopedSubscriberGroupDeletionService $deletions,
    ) {
        abort_unless(
            $deletions->deleteSubGroup($request->user(), $group, $subGroup),
            403,
            'Subgroup cannot be deleted while it is outside scope, has subscribers, or has campaign history.'
        );

        return redirect()
            ->route('statamic.cp.newsletter.groups.edit', $group)
            ->with('success', 'Sub-group deleted.');
    }

    public function archive(
        Request $request,
        SubscriberGroup $group,
        SubscriberSubGroup $subGroup,
        ScopedSubscriberGroupDeletionService $deletions,
    ) {
        abort_unless(
            $deletions->archiveSubGroup($request->user(), $group, $subGroup),
            403,
            'Subgroup cannot be archived unless it is in scope and has campaign history.'
        );

        return redirect()
            ->route('statamic.cp.newsletter.groups.edit', $group)
            ->with('success', 'Sub-group archived.');
    }

    public function restore(
        Request $request,
        SubscriberGroup $group,
        SubscriberSubGroup $subGroup,
        ScopedSubscriberGroupDeletionService $deletions,
    ) {
        abort_unless(
            $deletions->restoreSubGroup($request->user(), $group, $subGroup),
            403,
            'Subgroup can only be restored when it is archived, in scope, and its parent group is active.',
        );

        return redirect()
            ->route('statamic.cp.newsletter.groups.edit', $group)
            ->with('success', 'Sub-group restored.');
    }
}
