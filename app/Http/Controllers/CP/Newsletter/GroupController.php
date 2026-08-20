<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\SubscriberGroup;
use App\Services\Newsletter\CollectionRegistry;
use App\Services\Newsletter\ScopedSubscriberGroupDeletionService;
use App\Services\Newsletter\ScopedSubscriberGroupProductSelector;
use App\Support\Platform\Ownership\SubscriberGroupOwnershipWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GroupController extends Controller
{
    public function index(
        Request $request,
        ScopedSubscriberGroupProductSelector $products,
    ) {
        $groups = $products->groupsFor($request->user());
        $groups->loadCount(['subGroups']);
        $groups->load(['subGroups' => fn ($q) => $q->withCount('subscribers')]);

        return view('newsletter.cp.groups.index', [
            'groups' => $groups,
            'collectionOptions' => app(CollectionRegistry::class)->options(),
        ]);
    }

    public function create(
        Request $request,
        ScopedSubscriberGroupProductSelector $products,
    )
    {
        $collectionOptions = app(CollectionRegistry::class)->options();
        $scopedProducts = $products->productsFor($request->user())
            ->filter(fn ($product) => array_key_exists($product->primary_collection_handle, $collectionOptions))
            ->values();

        abort_if($scopedProducts->isEmpty(), 403, 'No active audience product scope is available for this operator.');

        return view('newsletter.cp.groups.create', [
            'products' => $scopedProducts,
            'collectionOptions' => $collectionOptions,
        ]);
    }

    public function store(
        Request $request,
        ScopedSubscriberGroupProductSelector $products,
        SubscriberGroupOwnershipWriter $groups,
    ) {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'name'        => 'required|string|max:255|unique:subscriber_groups,name',
            'description' => 'nullable|string',
        ]);

        $product = $products->resolve($request->user(), (int) $validated['product_id']);

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => 'Select an active product in your direct scope.',
            ]);
        }

        $collectionOptions = app(CollectionRegistry::class)->options();

        if (! array_key_exists($product->primary_collection_handle, $collectionOptions)) {
            throw ValidationException::withMessages([
                'product_id' => 'The selected product has no available newsletter collection.',
            ]);
        }

        $groups->createForProduct($product, [
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'collection_handle' => $product->primary_collection_handle,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group created.');
    }

    public function edit(
        Request $request,
        SubscriberGroup $group,
        ScopedSubscriberGroupProductSelector $products,
    ) {
        $product = $products->resolveGroup($request->user(), $group);

        abort_if($product === null, 403, 'Audience group is outside your active product scope.');

        $collectionOptions = app(CollectionRegistry::class)->options();

        abort_if(
            ! array_key_exists($product->primary_collection_handle, $collectionOptions),
            403,
            'Audience product collection is unavailable.'
        );

        $group->load(['subGroups' => fn ($q) => $q->withCount('subscribers')]);

        return view('newsletter.cp.groups.edit', [
            'group' => $group,
            'product' => $product,
            'collectionLabel' => $collectionOptions[$product->primary_collection_handle],
        ]);
    }

    public function update(
        Request $request,
        SubscriberGroup $group,
        ScopedSubscriberGroupProductSelector $products,
        SubscriberGroupOwnershipWriter $groups,
    ) {
        $product = $products->resolveGroup($request->user(), $group);

        abort_if($product === null, 403, 'Audience group is outside your active product scope.');
        abort_if(
            ! array_key_exists(
                $product->primary_collection_handle,
                app(CollectionRegistry::class)->options(),
            ),
            403,
            'Audience product collection is unavailable.'
        );

        $validated = $request->validate([
            'product_id' => 'required|integer',
            'name'        => 'required|string|max:255|unique:subscriber_groups,name,' . $group->id,
            'description' => 'nullable|string',
        ]);

        if ((int) $validated['product_id'] !== (int) $product->getKey()) {
            throw ValidationException::withMessages([
                'product_id' => 'Audience group ownership cannot be changed during editing.',
            ]);
        }

        $groups->updateForProduct($product, $group, [
            'name'        => $validated['name'],
            'slug'        => Str::slug($validated['name']),
            'collection_handle' => $product->primary_collection_handle,
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group updated.');
    }

    public function destroy(
        Request $request,
        SubscriberGroup $group,
        ScopedSubscriberGroupDeletionService $groups,
    ) {
        abort_unless(
            $groups->delete($request->user(), $group),
            403,
            'Audience group is outside your active product scope.',
        );

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group deleted.');
    }

    public function archive(
        Request $request,
        SubscriberGroup $group,
        ScopedSubscriberGroupDeletionService $groups,
    ) {
        abort_unless(
            $groups->archive($request->user(), $group),
            403,
            'Audience group cannot be archived unless it is in scope and has campaign history.',
        );

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group archived.');
    }

    public function restore(
        Request $request,
        SubscriberGroup $group,
        ScopedSubscriberGroupDeletionService $groups,
    ) {
        abort_unless(
            $groups->restore($request->user(), $group),
            403,
            'Audience group can only be restored when it is archived and inside your active product scope.',
        );

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group restored.');
    }
}
