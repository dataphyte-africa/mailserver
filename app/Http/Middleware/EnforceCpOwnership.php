<?php

namespace App\Http\Middleware;

use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\ProductForm;
use App\Models\ProductFormSubmission;
use App\Models\Subscriber;
use App\Models\SubscriberGroup;
use App\Models\SubscriberSubGroup;
use App\Services\Platform\CpOperatorScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Statamic\Facades\Entry;

class EnforceCpOwnership
{
    public function __construct(private readonly CpOperatorScope $scope) {}

    public function handle(Request $request, Closure $next): Response
    {
        $operator = $request->user();
        if ($this->scope->isSuper($operator)) {
            return $next($request);
        }

        $campaign = $request->route('campaign');
        if (is_string($campaign) && ctype_digit($campaign)) {
            $campaign = Campaign::findOrFail((int) $campaign);
        }
        if ($campaign instanceof Campaign) {
            abort_unless($this->scope->canCampaign($operator, $campaign), 403);
            $legacyCampaign = $this->scope->isVisibleLegacyCampaign($operator, $campaign);
            abort_if($legacyCampaign && ! $request->isMethod('GET'), 403);
            if (filled($campaign->entry_id)) {
                $entry = Entry::find($campaign->entry_id);
                $campaign->loadMissing('product');
                $ownedEntryMatches = $campaign->product
                    && (! filled($campaign->product->blueprint_handle)
                        || $entry?->blueprint()?->handle() === $campaign->product->blueprint_handle);
                abort_unless($entry && $entry->collectionHandle() === $campaign->collection
                    && ($legacyCampaign || $ownedEntryMatches), 403);
            }
        }

        $subscriber = $request->route('subscriber');
        if (is_string($subscriber) && ctype_digit($subscriber)) {
            $subscriber = Subscriber::findOrFail((int) $subscriber);
        }
        if ($subscriber instanceof Subscriber) {
            abort_unless($this->scope->canSubscriber($operator, $subscriber), 403);
        }

        $group = $request->route('group');
        if (is_string($group) && ctype_digit($group)) {
            $group = SubscriberGroup::findOrFail((int) $group);
        }
        if ($group instanceof SubscriberGroup) {
            abort_unless($this->scope->canGroup($operator, $group), 403);
        }

        $subGroup = $request->route('subGroup');
        if ($subGroup instanceof SubscriberSubGroup) {
            abort_unless($group instanceof SubscriberGroup && (int) $subGroup->subscriber_group_id === (int) $group->id, 403);
        }

        $organisation = $request->route('organisation');
        if ($organisation instanceof Organisation) {
            abort_unless(in_array((int) $organisation->id, $this->scope->organisationIds($operator), true), 403);
        }

        $form = $request->route('productForm');
        if ($form instanceof ProductForm) {
            $allowed = $form->form_scope === 'organisation'
                ? in_array((int) $form->organisation_id, $this->scope->organisationIds($operator), true)
                : in_array((int) $form->product_id, $this->scope->productIds($operator), true)
                    && (int) $form->product?->organisation_id === (int) $form->organisation_id;
            abort_unless($allowed, 403);
        }

        $submission = $request->route('submission');
        if ($submission instanceof ProductFormSubmission) {
            abort_unless($form instanceof ProductForm && (int) $submission->product_form_id === (int) $form->id, 403);
        }

        return $next($request);
    }
}
