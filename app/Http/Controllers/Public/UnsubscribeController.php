<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This unsubscribe link has expired or is invalid.');
        }

        $subscriber = Subscriber::where('confirmation_token', $token)->firstOrFail();
        [$scopedGroup, $scopedCollection, $scopedLabel] = $this->resolveScope($request);

        return view('newsletter.public.unsubscribe', compact(
            'subscriber',
            'token',
            'scopedCollection',
            'scopedLabel'
        ));
    }

    public function process(Request $request, string $token)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'This unsubscribe link has expired or is invalid.');
        }

        $subscriber = Subscriber::where('confirmation_token', $token)->firstOrFail();
        [$scopedGroup, $scopedCollection, $scopedLabel] = $this->resolveScope($request);

        if ($scopedGroup) {
            $subGroupIds = $scopedGroup->subGroups()->pluck('id')->all();

            if ($subGroupIds !== []) {
                $subscriber->allSubGroups()->updateExistingPivot($subGroupIds, [
                    'unsubscribed_at' => now(),
                ]);
            }

            $this->syncSubscriberStatus($subscriber);

            return view('newsletter.public.unsubscribed', compact(
                'subscriber',
                'scopedCollection',
                'scopedLabel'
            ));
        }

        $subscriber->update([
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        $subscriber->allSubGroups()->update(['unsubscribed_at' => now()]);

        return view('newsletter.public.unsubscribed', compact(
            'subscriber',
            'scopedCollection',
            'scopedLabel'
        ));
    }

    private function resolveScope(Request $request): array
    {
        $collectionHandle = $request->query('collection');

        if (! is_string($collectionHandle) || trim($collectionHandle) === '') {
            return [null, null, null];
        }

        $group = SubscriberGroup::with('subGroups')
            ->where('collection_handle', $collectionHandle)
            ->first();

        if (! $group) {
            return [null, null, null];
        }

        $label = config("newsletter.collections.{$collectionHandle}.label", $group->name);

        return [$group, $collectionHandle, $label];
    }

    private function syncSubscriberStatus(Subscriber $subscriber): void
    {
        $hasActiveSubscriptions = $subscriber->subGroups()->exists();

        $subscriber->update([
            'status' => $hasActiveSubscriptions ? 'active' : 'unsubscribed',
            'unsubscribed_at' => $hasActiveSubscriptions ? null : now(),
        ]);
    }
}
