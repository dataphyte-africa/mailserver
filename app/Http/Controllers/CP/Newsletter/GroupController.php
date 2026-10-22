<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\SubscriberGroup;
use App\Models\SubscriberGroupEngagement;
use App\Models\Organisation;
use App\Models\Subscriber;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\GroupEngagementService;
use App\Services\Newsletter\OrganisationMailOptOut;
use App\Services\Newsletter\AudienceGrowthService;
use App\Services\Platform\CpOperatorScope;
use App\Services\Newsletter\CollectionRegistry;
use App\Services\Newsletter\ScopedSubscriberGroupDeletionService;
use App\Services\Newsletter\ScopedSubscriberGroupProductSelector;
use App\Support\Platform\Ownership\SubscriberGroupOwnershipWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GroupController extends Controller
{
    public function index(
        Request $request,
        ScopedSubscriberGroupProductSelector $products,
    ) {
        $groups = $products->groupsFor($request->user());
        if (SubscriberGroup::query()->where('slug', 'like', 'foundation-private-%')->exists()) {
            $groups = $groups->reject(fn (SubscriberGroup $group) => $group->slug === 'foundation');
        }
        $activeCounts = DB::table('subscriber_sub_group as membership')
            ->join('subscriber_sub_groups as preference', 'preference.id', '=', 'membership.subscriber_sub_group_id')
            ->join('subscribers', 'subscribers.id', '=', 'membership.subscriber_id')
            ->whereIn('preference.subscriber_group_id', $groups->modelKeys())
            ->where('preference.purpose', 'preference')
            ->whereNull('membership.unsubscribed_at')
            ->where('subscribers.status', 'active')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')
                ->from('organisation_subscriber_opt_outs as opt_out')
                ->join('subscriber_groups as owned_group', 'owned_group.organisation_id', '=', 'opt_out.organisation_id')
                ->whereColumn('owned_group.id', 'preference.subscriber_group_id')
                ->whereColumn('opt_out.subscriber_id', 'subscribers.id'))
            ->selectRaw('preference.subscriber_group_id, COUNT(DISTINCT subscribers.id) AS total')
            ->groupBy('preference.subscriber_group_id')->pluck('total', 'subscriber_group_id');
        $engagements = SubscriberGroupEngagement::query()
            ->whereIn('subscriber_group_id', $groups->modelKeys())
            ->selectRaw('subscriber_group_id, SUM(CASE WHEN first_joined_at >= ? THEN 1 ELSE 0 END) AS new_this_month, SUM(CASE WHEN first_joined_at >= ? AND first_joined_at < ? THEN 1 ELSE 0 END) AS new_last_month', [
                now()->startOfMonth(), now()->subMonth()->startOfMonth(), now()->startOfMonth(),
            ])
            ->groupBy('subscriber_group_id')->get()->keyBy('subscriber_group_id');
        $ratingCounts = SubscriberGroupEngagement::query()
            ->selectRaw('subscriber_group_id, rating, COUNT(*) AS total')
            ->whereIn('subscriber_group_id', $groups->modelKeys())
            ->where(fn ($query) => $query->whereIn('rating', ['unsubscribed', 'suppressed'])
                ->orWhere(fn ($members) => $members->where('is_member', true)
                    ->whereHas('subscriber', fn ($subscriber) => $subscriber->where('status', 'active'))))
            ->groupBy('subscriber_group_id', 'rating')->get()
            ->groupBy('subscriber_group_id');

        return view('newsletter.cp.groups.index', [
            'groups' => $groups,
            'activeCounts' => $activeCounts,
            'engagements' => $engagements,
            'ratingCounts' => $ratingCounts,
            'collectionOptions' => app(CollectionRegistry::class)->options(),
        ]);
    }

    public function create(Request $request, CpOperatorScope $scope)
    {
        $organisations = Organisation::query()->where('status', 'active')
            ->whereKey($scope->organisationIds($request->user()))->orderBy('name')->get();

        abort_if($organisations->isEmpty(), 403, 'No active organisation scope is available for this operator.');

        return view('newsletter.cp.groups.create', [
            'organisations' => $organisations,
        ]);
    }

    public function store(
        Request $request,
        CpOperatorScope $scope,
    ) {
        $validated = $request->validate([
            'organisation_id' => 'required|integer',
            'name'        => 'required|string|max:255|unique:subscriber_groups,name',
            'description' => 'nullable|string',
        ]);

        $organisation = Organisation::query()->where('status', 'active')
            ->whereKey($scope->organisationIds($request->user()))
            ->find((int) $validated['organisation_id']);
        if ($organisation === null) {
            throw ValidationException::withMessages([
                'organisation_id' => 'Select an active organisation in your scope.',
            ]);
        }

        DB::transaction(function () use ($validated, $organisation): void {
            $group = SubscriberGroup::query()->create([
                'organisation_id' => $organisation->getKey(),
                'product_id' => null,
                'audience_scope' => 'organisation_private',
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
                'is_public_preference' => false,
            ]);
            SubscriberSubGroup::query()->create([
                'subscriber_group_id' => $group->getKey(),
                'name' => 'Members',
                'slug' => 'members',
                'purpose' => 'preference',
            ]);
            app(GroupEngagementService::class)->ensureSegments($group);
        });

        return redirect()
            ->route('statamic.cp.newsletter.groups.index')
            ->with('success', 'Group created.');
    }

    public function edit(
        Request $request,
        SubscriberGroup $group,
        ScopedSubscriberGroupProductSelector $products,
    ) {
        if ($group->isPrivateAudience()) {
            abort_unless(app(CpOperatorScope::class)->canGroup($request->user(), $group), 403);
            $members = Subscriber::query()->whereHas('allSubGroups', fn ($query) => $query
                ->where('subscriber_group_id', $group->getKey())
                ->where('purpose', 'preference'))
                ->with(['subGroups.group.product', 'allSubGroups' => fn ($query) => $query
                    ->where('subscriber_group_id', $group->getKey())->where('purpose', 'preference')])
                ->orderBy('email')->paginate(50);

            return view('newsletter.cp.groups.edit', [
                'group' => $group,
                'product' => null,
                'members' => $members,
                'growth' => app(AudienceGrowthService::class)->forGroup($group),
                'ratingCounts' => $this->ratingCounts($group),
            ]);
        }

        $product = $products->resolveGroup($request->user(), $group);

        abort_if($product === null, 403, 'Audience group is outside your active product scope.');

        $collectionOptions = app(CollectionRegistry::class)->options();

        abort_if(
            ! array_key_exists($product->primary_collection_handle, $collectionOptions),
            403,
            'Audience product collection is unavailable.'
        );

        return view('newsletter.cp.groups.edit', [
            'group' => $group,
            'product' => $product,
            'collectionLabel' => $collectionOptions[$product->primary_collection_handle],
            'growth' => app(AudienceGrowthService::class)->forGroup($group),
            'ratingCounts' => $this->ratingCounts($group),
        ]);
    }

    private function ratingCounts(SubscriberGroup $group)
    {
        return SubscriberGroupEngagement::query()
            ->where('subscriber_group_id', $group->getKey())
            ->where(fn ($ratings) => $ratings->whereIn('rating', ['unsubscribed', 'suppressed'])
                ->orWhere(fn ($members) => $members->where('is_member', true)
                    ->whereHas('subscriber', fn ($subscriber) => $subscriber->where('status', 'active'))))
            ->selectRaw('rating, COUNT(*) AS total')
            ->groupBy('rating')->pluck('total', 'rating');
    }

    public function update(
        Request $request,
        SubscriberGroup $group,
        ScopedSubscriberGroupProductSelector $products,
        SubscriberGroupOwnershipWriter $groups,
    ) {
        if ($group->isPrivateAudience()) {
            abort_unless(app(CpOperatorScope::class)->canGroup($request->user(), $group), 403);
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:subscriber_groups,name,'.$group->id,
                'description' => 'nullable|string',
            ]);
            $group->update([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']),
                'description' => $validated['description'] ?? null,
            ]);

            return redirect()->route('statamic.cp.newsletter.groups.index')->with('success', 'Group updated.');
        }

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

    public function addMember(Request $request, SubscriberGroup $group, CpOperatorScope $scope, GroupEngagementService $engagement)
    {
        abort_unless($group->isPrivateAudience() && ! $group->isArchived()
            && $scope->canGroup($request->user(), $group), 403);
        $data = $request->validate([
            'email' => 'required|email|max:255',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'source' => 'required|string|max:255',
            'permission_confirmed' => 'accepted',
        ]);

        $this->enrollPrivateMember($group, $data, $engagement);

        return back()->with('success', 'Member added to the private audience.');
    }

    public function importMembers(Request $request, SubscriberGroup $group, CpOperatorScope $scope, GroupEngagementService $engagement)
    {
        abort_unless($group->isPrivateAudience() && ! $group->isArchived()
            && $scope->canGroup($request->user(), $group), 403);
        $data = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
            'source' => 'required|string|max:255',
            'permission_confirmed' => 'accepted',
        ]);
        $handle = fopen($data['csv_file']->getRealPath(), 'r');
        abort_unless($handle !== false, 422, 'The CSV file could not be opened.');
        try {
            $headers = array_map(fn ($header) => strtolower(trim((string) $header)), fgetcsv($handle) ?: []);
            if (! in_array('email', $headers, true)) {
                throw ValidationException::withMessages(['csv_file' => 'CSV must include an email header.']);
            }
            $rowCount = 0;
            while (fgetcsv($handle) !== false) {
                if (++$rowCount > 2000) {
                    throw ValidationException::withMessages(['csv_file' => 'Import is limited to 2,000 rows at a time.']);
                }
            }
            rewind($handle);
            fgetcsv($handle);
            $added = 0;
            $skipped = 0;
            DB::transaction(function () use ($handle, $headers, $group, $data, $engagement, &$added, &$skipped): void {
                while (($values = fgetcsv($handle)) !== false) {
                    $row = array_combine($headers, array_pad(array_slice($values, 0, count($headers)), count($headers), ''));
                    $email = strtolower(trim((string) ($row['email'] ?? '')));
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $skipped++;
                        continue;
                    }
                    try {
                        $this->enrollPrivateMember($group, [
                            'email' => $email,
                            'first_name' => trim((string) ($row['first_name'] ?? '')),
                            'last_name' => trim((string) ($row['last_name'] ?? '')),
                            'source' => $data['source'],
                        ], $engagement) ? $added++ : $skipped++;
                    } catch (ValidationException) {
                        $skipped++;
                    }
                }
            });
        } finally {
            fclose($handle);
        }

        return back()->with('success', "Import complete: {$added} added, {$skipped} skipped (including duplicates and opt-outs).");
    }

    public function removeMember(Request $request, SubscriberGroup $group, Subscriber $member, CpOperatorScope $scope, GroupEngagementService $engagement)
    {
        abort_unless($group->isPrivateAudience() && $scope->canGroup($request->user(), $group), 403);
        $members = $group->subGroups()->where('slug', 'members')->where('purpose', 'preference')->firstOrFail();
        abort_unless($member->subGroups()->whereKey($members->getKey())->exists(), 404);
        $member->allSubGroups()->updateExistingPivot($members->getKey(), ['unsubscribed_at' => now()]);
        if (! $member->subGroups()->where('purpose', 'preference')->exists()) {
            $member->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
        }
        $engagement->sync($member->fresh(), $group);

        return back()->with('success', 'Member removed from the private audience.');
    }

    private function enrollPrivateMember(SubscriberGroup $group, array $data, GroupEngagementService $engagement): bool
    {
        [$subscriber, $added] = DB::transaction(function () use ($group, $data): array {
            $subscriber = Subscriber::query()->firstOrCreate(
                ['email' => strtolower(trim($data['email']))],
                ['first_name' => $data['first_name'] ?? null, 'last_name' => $data['last_name'] ?? null, 'status' => 'active'],
            );
            if (in_array($subscriber->status, ['unsubscribed', 'bounced', 'complained', 'erased'], true)) {
                throw ValidationException::withMessages(['email' => 'This address is unsubscribed or suppressed and cannot be added.']);
            }
            if (app(OrganisationMailOptOut::class)->has((int) $group->organisation_id, (int) $subscriber->getKey())) {
                throw ValidationException::withMessages(['email' => 'This address opted out of this organisation.']);
            }
            $members = $group->subGroups()->where('slug', 'members')->where('purpose', 'preference')->firstOrFail();
            $existing = $subscriber->allSubGroups()->whereKey($members->getKey())->first();
            if ($existing !== null && $existing->pivot->unsubscribed_at !== null) {
                throw ValidationException::withMessages(['email' => 'This address previously left this private audience.']);
            }
            if ($existing === null) {
                $subscriber->allSubGroups()->attach($members->getKey(), [
                    'subscribed_at' => now(), 'private_source' => $data['source'],
                ]);
            }

            return [$subscriber, $existing === null];
        });
        if ($added) {
            $engagement->sync($subscriber, $group);
        }

        return $added;
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
