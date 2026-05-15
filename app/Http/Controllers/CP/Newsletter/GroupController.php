<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\SubscriberGroup;
use App\Services\Newsletter\CollectionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function index()
    {
        $groups = SubscriberGroup::withCount(['subGroups'])
            ->with(['subGroups' => fn ($q) => $q->withCount('subscribers')])
            ->orderBy('name')
            ->get();

        return view('newsletter.cp.groups.index', [
            'groups' => $groups,
            'collectionOptions' => app(CollectionRegistry::class)->options(),
        ]);
    }

    public function create()
    {
        return view('newsletter.cp.groups.create', [
            'collectionOptions' => app(CollectionRegistry::class)->options(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:subscriber_groups,name',
            'collection_handle' => 'required|' . app(CollectionRegistry::class)->validationRule(),
            'description' => 'nullable|string',
        ]);

        SubscriberGroup::create([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'collection_handle' => $validated['collection_handle'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group created.');
    }

    public function edit(SubscriberGroup $group)
    {
        $group->load(['subGroups' => fn ($q) => $q->withCount('subscribers')]);

        return view('newsletter.cp.groups.edit', [
            'group' => $group,
            'collectionOptions' => app(CollectionRegistry::class)->options(),
        ]);
    }

    public function update(Request $request, SubscriberGroup $group)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:subscriber_groups,name,' . $group->id,
            'collection_handle' => 'required|' . app(CollectionRegistry::class)->validationRule(),
            'description' => 'nullable|string',
        ]);

        $group->update([
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'collection_handle' => $validated['collection_handle'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group updated.');
    }

    public function destroy(SubscriberGroup $group)
    {
        $group->delete();

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group deleted.');
    }
}
