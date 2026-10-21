<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\NewsletterPublicUrlService;
use App\Services\Newsletter\GroupEngagementService;
use App\Services\Newsletter\OrganisationMailOptOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PreferencesController extends Controller
{
    public function show(Request $request, string $token, NewsletterPublicUrlService $urls, string $mode = 'preferences')
    {
        abort_unless($urls->hasValidSignature($request), 403, 'This preferences link has expired or is invalid.');

        $subscriber = Subscriber::query()->where('confirmation_token', $token)->firstOrFail();
        if (! filled($request->query('organisation')) && ! filled($request->query('collection'))) {
            $legacyOrganisations = $this->legacyOrganisations($subscriber);
            if ($legacyOrganisations->count() !== 1) {
                return view('newsletter.public.choose-organisation', [
                    'subscriber' => $subscriber,
                    'organisations' => $legacyOrganisations,
                    'routeName' => $mode === 'unsubscribe' ? 'newsletter.unsubscribe.show' : 'newsletter.preferences.show',
                ]);
            }
        }
        $organisation = $this->organisation($request, $subscriber);
        $productContext = $this->productContext($request, $organisation);
        if ($mode === 'unsubscribe' && $request->filled('private_group')) {
            $group = $this->privateGroup($request, $organisation);

            return view('newsletter.public.private-unsubscribe', [
                'subscriber' => $subscriber,
                'organisation' => $organisation,
                'group' => $group,
                'formUrl' => $urls->unsubscribeProcessUrl($subscriber, $organisation->primary_collection_handle, $organisation, $group->getKey()),
                'preferencesUrl' => $urls->preferencesUrl($subscriber, $organisation->primary_collection_handle, $organisation),
                'completed' => false,
            ]);
        }
        $activeIds = $this->activeIds($subscriber, $organisation);

        return view('newsletter.public.preferences', [
            'subscriber' => $subscriber,
            'organisation' => $organisation,
            'groups' => $this->visibleGroups($organisation, $activeIds),
            'activeSubGroupIds' => $activeIds,
            'organisationOptedOut' => app(OrganisationMailOptOut::class)->has((int) $organisation->getKey(), (int) $subscriber->getKey()),
            'mode' => $mode,
            'formUrl' => $mode === 'unsubscribe'
                ? $urls->unsubscribeProcessUrl($subscriber, $organisation->primary_collection_handle, $productContext)
                : $urls->preferencesUpdateUrl($subscriber, $organisation->primary_collection_handle, $productContext),
            'unsubscribeUrl' => $urls->unsubscribeUrl($subscriber, $organisation->primary_collection_handle, $productContext),
        ]);
    }

    public function update(Request $request, string $token, NewsletterPublicUrlService $urls, GroupEngagementService $engagement)
    {
        abort_unless($urls->hasValidSignature($request), 403, 'This preferences link has expired or is invalid.');

        $subscriber = Subscriber::query()->where('confirmation_token', $token)->firstOrFail();
        $organisation = $this->organisation($request, $subscriber);
        $optOut = app(OrganisationMailOptOut::class);
        $productContext = $this->productContext($request, $organisation);
        if ($request->filled('private_group')) {
            $group = $this->privateGroup($request, $organisation);
            $members = $group->subGroups()->where('slug', 'members')->where('purpose', 'preference')->firstOrFail();
            DB::transaction(function () use ($subscriber, $members): void {
                $subscriber->allSubGroups()->updateExistingPivot($members->getKey(), ['unsubscribed_at' => now()]);
                if (! $subscriber->subGroups()->where('purpose', 'preference')->exists()) {
                    $subscriber->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
                }
            });
            $engagement->sync($subscriber->fresh(), $group);

            return view('newsletter.public.private-unsubscribe', [
                'subscriber' => $subscriber,
                'organisation' => $organisation,
                'group' => $group,
                'preferencesUrl' => $urls->preferencesUrl($subscriber, $organisation->primary_collection_handle, $organisation),
                'completed' => true,
            ]);
        }
        $validated = $request->validate([
            'sub_groups' => ['nullable', 'array'],
            'sub_groups.*' => ['integer', 'distinct'],
            'audience_groups' => ['nullable', 'array'],
            'audience_groups.*' => ['integer', 'distinct'],
            'organisation_opt_out' => ['nullable', 'boolean'],
        ]);
        $organisationOptedOut = (bool) ($validated['organisation_opt_out'] ?? false);
        $current = $this->activeIds($subscriber, $organisation);
        $published = $this->publishedIds($organisation);
        $selected = $organisationOptedOut ? [] : ($request->has('audience_groups')
            ? $this->selectedIdsForGroups($organisation, $current, $published, $validated['audience_groups'] ?? [])
            : array_map('intval', $validated['sub_groups'] ?? []));

        abort_if(in_array($subscriber->status, ['erased', 'bounced', 'complained'], true)
            && array_diff($selected, $current) !== [], 422,
            'New subscriptions are unavailable for this address.');

        abort_if(array_diff($selected, array_unique(array_merge($current, $published))) !== [], 422,
            'One or more selected preferences are unavailable for this organisation.');

        DB::transaction(function () use ($subscriber, $selected, $current, $organisation, $organisationOptedOut, $optOut): void {
            $optOut->set((int) $organisation->getKey(), (int) $subscriber->getKey(), $organisationOptedOut);
            $remove = array_diff($current, $selected);
            if ($remove !== []) {
                $subscriber->allSubGroups()->updateExistingPivot($remove, ['unsubscribed_at' => now()]);
            }

            $add = array_diff($selected, $current);
            if ($add !== []) {
                $existing = $subscriber->allSubGroups()->whereIn('subscriber_sub_groups.id', $add)
                    ->pluck('subscriber_sub_groups.id')->map(fn ($id) => (int) $id)->all();
                foreach ($add as $id) {
                    if (in_array($id, $existing, true)) {
                        $subscriber->allSubGroups()->updateExistingPivot($id, [
                            'subscribed_at' => now(), 'unsubscribed_at' => null,
                        ]);
                    } else {
                        $subscriber->allSubGroups()->attach($id, [
                            'subscribed_at' => now(), 'unsubscribed_at' => null,
                        ]);
                    }
                }
            }

            $hasAnyMembership = $subscriber->subGroups()->where('purpose', 'preference')->exists();
            $subscriber->update([
                'status' => in_array($subscriber->status, ['erased', 'bounced', 'complained'], true)
                    ? $subscriber->status
                    : ($hasAnyMembership ? 'active' : 'unsubscribed'),
                'unsubscribed_at' => $hasAnyMembership ? null : now(),
            ]);
        });

        $affectedGroupIds = SubscriberSubGroup::query()->whereIn('id', array_unique(array_merge($current, $selected)))
            ->pluck('subscriber_group_id')->unique()->all();
        $affectedGroupIds = array_unique(array_merge($affectedGroupIds, SubscriberGroup::query()
            ->where('organisation_id', $organisation->getKey())
            ->whereHas('subGroups', fn ($groups) => $groups->whereHas('subscribers', fn ($members) => $members->whereKey($subscriber->getKey())))
            ->pluck('id')->all()));
        $engagement->syncForSubscriber($subscriber->fresh(), $affectedGroupIds);

        $remainingInOrganisation = $this->activeIds($subscriber, $organisation);

        return view($organisationOptedOut || $remainingInOrganisation === [] ? 'newsletter.public.unsubscribed' : 'newsletter.public.preferences-saved', [
            'subscriber' => $subscriber,
            'organisation' => $organisation,
            'organisationOptedOut' => $organisationOptedOut,
            'preferencesUrl' => $urls->preferencesUrl($subscriber, $organisation->primary_collection_handle, $productContext),
        ]);
    }

    private function privateGroup(Request $request, Organisation $organisation): SubscriberGroup
    {
        $id = $request->query('private_group');
        abort_unless(is_scalar($id) && ctype_digit((string) $id), 403);

        return SubscriberGroup::query()->whereKey((int) $id)
            ->where('organisation_id', $organisation->getKey())
            ->where('audience_scope', 'organisation_private')
            ->whereNull('product_id')->firstOrFail();
    }

    private function organisation(Request $request, Subscriber $subscriber): Organisation
    {
        $id = $request->query('organisation');
        $handle = $request->query('collection');

        if (filled($id)) {
            $organisation = Organisation::query()->where('status', 'active')->findOrFail($id);
            abort_if(filled($handle) && $handle !== $organisation->primary_collection_handle, 403);

            return $organisation;
        }

        // Legacy signed product links have only a collection handle. Never fall back to a global audience list.
        if (! is_string($handle) || ! filled($handle)) {
            $organisations = $this->legacyOrganisations($subscriber);
            abort_unless($organisations->count() === 1, 422);

            return $organisations->first();
        }
        $matches = Organisation::query()->where('status', 'active')
            ->where('primary_collection_handle', $handle)->limit(2)->get();
        abort_unless($matches->count() === 1, 404);

        return $matches->first();
    }

    private function legacyOrganisations(Subscriber $subscriber)
    {
        $ids = $subscriber->allSubGroups()
            ->whereHas('group.product', fn ($product) => $product
                ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id'))
            ->with('group')
            ->get()
            ->pluck('group.organisation_id')
            ->filter()
            ->unique()
            ->values();

        $legacyHandles = $subscriber->allSubGroups()
            ->where('purpose', 'preference')
            ->whereHas('group', fn ($group) => $group->whereNull('organisation_id')->whereNull('product_id'))
            ->with('group')->get()->pluck('group.collection_handle')->filter()->unique()
            ->filter(fn ($handle) => Organisation::query()->where('status', 'active')
                ->where('primary_collection_handle', $handle)->count() === 1);
        $legacyOrganisationIds = Organisation::query()->where('status', 'active')
            ->whereIn('primary_collection_handle', $legacyHandles)->pluck('id');

        return Organisation::query()->where('status', 'active')
            ->whereKey($ids->merge($legacyOrganisationIds)->unique())->orderBy('name')->get();
    }

    private function activeIds(Subscriber $subscriber, Organisation $organisation): array
    {
        return $subscriber->subGroups()
            ->where('purpose', 'preference')
            ->whereHas('group', fn ($query) => $query
                ->where('organisation_id', $organisation->getKey())
                ->whereHas('product', fn ($product) => $product
                    ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id')
                    ->whereColumn('products.primary_collection_handle', 'subscriber_groups.collection_handle')))
            ->pluck('subscriber_sub_groups.id')->map(fn ($id) => (int) $id)->all();
    }

    private function productContext(Request $request, Organisation $organisation): Organisation|int
    {
        $productId = $request->query('product');
        if (! filled($productId)) {
            return $organisation;
        }

        abort_unless(is_scalar($productId) && ctype_digit((string) $productId), 403);
        $product = Product::query()->findOrFail((int) $productId);
        abort_unless((int) $product->organisation_id === (int) $organisation->id
            && $product->primary_collection_handle === $organisation->primary_collection_handle, 403);

        return (int) $product->id;
    }

    private function publishedIds(Organisation $organisation): array
    {
        return SubscriberSubGroup::query()
            ->where('purpose', 'preference')
            ->whereNull('archived_at')
            ->whereHas('group', fn ($query) => $query
                ->where('organisation_id', $organisation->getKey())
                ->where('is_public_preference', true)
                ->whereNull('archived_at')
                ->whereHas('product', fn ($product) => $product
                    ->where('status', 'active')
                    ->whereColumn('products.organisation_id', 'subscriber_groups.organisation_id')
                    ->whereColumn('products.primary_collection_handle', 'subscriber_groups.collection_handle')))
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function visibleGroups(Organisation $organisation, array $activeIds)
    {
        $visibleIds = array_unique(array_merge($activeIds, $this->publishedIds($organisation)));

        return SubscriberGroup::query()
            ->whereHas('subGroups', fn ($query) => $query->whereKey($visibleIds))
            ->with(['product', 'subGroups' => fn ($query) => $query->whereKey($visibleIds)->orderBy('name')])
            ->orderBy('name')->get();
    }

    private function selectedIdsForGroups(Organisation $organisation, array $current, array $published, array $groupIds): array
    {
        $groups = $this->visibleGroups($organisation, $current)->keyBy('id');
        $selected = [];
        foreach (array_map('intval', $groupIds) as $id) {
            $group = $groups->get($id);
            abort_unless($group, 422, 'One or more selected audiences are unavailable.');
            $choices = $group->subGroups->pluck('id')->map(fn ($value) => (int) $value)->all();
            $existing = array_values(array_intersect($choices, $current));
            $publishedChoice = array_values(array_intersect($choices, $published));
            abort_if($existing === [] && $publishedChoice === [], 422, 'This audience is unavailable for new subscriptions.');
            array_push($selected, ...($existing ?: [reset($publishedChoice)]));
        }

        return array_unique($selected);
    }
}
