<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Newsletter\NewsletterPublicUrlService;
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
        $activeIds = $this->activeIds($subscriber, $organisation);

        return view('newsletter.public.preferences', [
            'subscriber' => $subscriber,
            'organisation' => $organisation,
            'groups' => $this->visibleGroups($organisation, $activeIds),
            'activeSubGroupIds' => $activeIds,
            'mode' => $mode,
            'formUrl' => $mode === 'unsubscribe'
                ? $urls->unsubscribeProcessUrl($subscriber, $organisation->primary_collection_handle, $productContext)
                : $urls->preferencesUpdateUrl($subscriber, $organisation->primary_collection_handle, $productContext),
            'unsubscribeUrl' => $urls->unsubscribeUrl($subscriber, $organisation->primary_collection_handle, $productContext),
        ]);
    }

    public function update(Request $request, string $token, NewsletterPublicUrlService $urls)
    {
        abort_unless($urls->hasValidSignature($request), 403, 'This preferences link has expired or is invalid.');

        $subscriber = Subscriber::query()->where('confirmation_token', $token)->firstOrFail();
        $organisation = $this->organisation($request, $subscriber);
        $productContext = $this->productContext($request, $organisation);
        $validated = $request->validate([
            'sub_groups' => ['nullable', 'array'],
            'sub_groups.*' => ['integer', 'distinct'],
        ]);
        $selected = array_map('intval', $validated['sub_groups'] ?? []);
        $current = $this->activeIds($subscriber, $organisation);
        $published = $this->publishedIds($organisation);

        abort_if(in_array($subscriber->status, ['erased', 'bounced', 'complained'], true)
            && array_diff($selected, $current) !== [], 422,
            'New subscriptions are unavailable for this address.');

        abort_if(array_diff($selected, array_unique(array_merge($current, $published))) !== [], 422,
            'One or more selected preferences are unavailable for this organisation.');

        DB::transaction(function () use ($subscriber, $selected, $current): void {
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

            $hasAnyMembership = $subscriber->subGroups()->exists();
            $subscriber->update([
                'status' => in_array($subscriber->status, ['erased', 'bounced', 'complained'], true)
                    ? $subscriber->status
                    : ($hasAnyMembership
                        ? ($subscriber->status === 'active' ? 'active' : 'pending')
                        : 'unsubscribed'),
                'unsubscribed_at' => $hasAnyMembership ? null : now(),
            ]);
        });

        $remainingInOrganisation = $this->activeIds($subscriber, $organisation);

        return view($remainingInOrganisation === [] ? 'newsletter.public.unsubscribed' : 'newsletter.public.preferences-saved', [
            'subscriber' => $subscriber,
            'organisation' => $organisation,
            'preferencesUrl' => $urls->preferencesUrl($subscriber, $organisation->primary_collection_handle, $productContext),
        ]);
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

        return Organisation::query()->where('status', 'active')->whereKey($ids)->orderBy('name')->get();
    }

    private function activeIds(Subscriber $subscriber, Organisation $organisation): array
    {
        return $subscriber->subGroups()
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
            ->where('organisation_id', $organisation->getKey())
            ->whereHas('subGroups', fn ($query) => $query->whereKey($visibleIds))
            ->with(['product', 'subGroups' => fn ($query) => $query->whereKey($visibleIds)->orderBy('name')])
            ->orderBy('name')->get();
    }
}
