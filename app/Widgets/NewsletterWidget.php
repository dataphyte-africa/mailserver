<?php

namespace App\Widgets;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\ProductForm;
use App\Models\Subscriber;
use App\Models\WebhookLog;
use App\Services\Platform\CpOperatorScope;
use Statamic\Widgets\Widget;

/**
 * Newsletter dashboard widget.
 *
 * Add to CP dashboard via: Appearance > Configure Dashboard > Add Widget > "newsletter"
 *
 * Configurable options (in dashboard.yaml / CP widget picker):
 *   days: 30          — lookback window for stats
 *   collection: ''    — filter by collection handle (blank = all)
 */
class NewsletterWidget extends Widget
{
    protected static $handle = 'newsletter';

    public function html()
    {
        $scope = app(CpOperatorScope::class);
        $operator = request()->user();
        $days       = (int) ($this->config('days', 30));
        $collection = $this->config('collection', null);

        // Recent campaigns
        $recentCampaigns = $scope->campaignQuery($operator)
            ->when($collection, fn ($q) => $q->where('collection', $collection))
            ->whereIn('status', ['sent', 'sending', 'scheduled'])
            ->orderByDesc('updated_at')
            ->limit(5)
            ->withCount([
                'sends',
                'sends as opened_count' => fn ($q) => $q->whereNotNull('opened_at'),
                'sends as clicked_count' => fn ($q) => $q->whereNotNull('clicked_at'),
            ])
            ->get();

        // Period totals
        $periodQuery = CampaignSend::query()
            ->where('sent_at', '>=', now()->subDays($days))
            ->when(! $scope->isSuper($operator), fn ($q) => $q->whereHas('campaign', fn ($campaign) => $scope->restrictCampaigns($campaign, $operator)))
            ->when($collection, fn ($q) => $q->whereHas(
                'campaign', fn ($cq) => $cq->where('collection', $collection)
            ));

        $totals = (clone $periodQuery)
            ->selectRaw('
                COUNT(*) as sent,
                SUM(status IN ("delivered","opened","clicked")) as delivered,
                SUM(opened_at IS NOT NULL) as opened,
                SUM(clicked_at IS NOT NULL) as clicked
            ')
            ->first();

        // Subscriber counts
        $subscriberStats = [
            'pending'      => $scope->subscriberQuery($operator)->where('status', 'pending')->count(),
            'active'       => $scope->subscriberQuery($operator)->where('status', 'active')->count(),
            'unsubscribed' => $scope->subscriberQuery($operator)->where('status', 'unsubscribed')->count(),
            'bounced'      => $scope->subscriberQuery($operator)->where('status', 'bounced')->count(),
            'complained'   => $scope->subscriberQuery($operator)->where('status', 'complained')->count(),
        ];

        // Webhook health (last 24h)
        $webhookFailed = $scope->isSuper($operator) ? WebhookLog::where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('error')
            ->count() : 0;

        $data = [
            'days'             => $days,
            'collection'       => $collection,
            'recentCampaigns'  => $recentCampaigns,
            'totals'           => $totals,
            'subscriberStats'  => $subscriberStats,
            'webhookFailed'    => $webhookFailed,
        ];

        if (! $scope->isSuper($operator)) {
            $organisationIds = $scope->organisationIds($operator);
            $productIds = $scope->productIds($operator);
            $organisations = Organisation::query()->whereKey($organisationIds)->where('status', 'active')
                ->orderBy('name')->get();
            $organisationIds = $organisations->modelKeys();
            $products = Product::query()->whereKey($productIds)->with('organisation')->orderBy('name')->get();
            $formsCount = ProductForm::query()->where('mode', 'subscription')->where(function ($query) use ($organisationIds, $productIds): void {
                $query->where(fn ($query) => $query->where('form_scope', 'product')->whereIn('product_id', $productIds)
                    ->whereHas('product', fn ($product) => $product->whereColumn('products.organisation_id', 'product_forms.organisation_id')))
                    ->orWhere(fn ($query) => $query->where('form_scope', 'organisation')->whereIn('organisation_id', $organisationIds));
            })->count();
            $legacySubscriptions = app(\App\Services\Newsletter\SubscriptionFormService::class);
            $organisationHandles = $organisations->pluck('primary_collection_handle')->all();
            $formsCount += \Statamic\Facades\Form::all()
                ->filter(fn ($form) => $legacySubscriptions->isNewsletterForm($form)
                    && $legacySubscriptions->submissionMode($form) === 'subscription'
                    && in_array($legacySubscriptions->collectionHandle($form), $organisationHandles, true))
                ->count();

            return view('newsletter.cp.widgets.organisation', $data + [
                'organisations' => $organisations,
                'products' => $products,
                'formsCount' => $formsCount,
                'draftCampaigns' => $scope->campaignQuery($operator)->where('status', 'draft')->count(),
            ]);
        }

        return view('newsletter.cp.widgets.newsletter', $data);
    }
}
