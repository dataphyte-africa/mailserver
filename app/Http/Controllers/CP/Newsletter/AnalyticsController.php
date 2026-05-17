<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Jobs\Newsletter\SyncCampaignStatsJob;
use App\Models\Campaign;
use App\Models\CampaignLinkClick;
use App\Models\CampaignSend;
use App\Models\WebhookLog;
use App\Services\Newsletter\CollectionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /* ------------------------------------------------------------------ */
    /* Overview dashboard                                                   */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $collection = $request->input('collection');
        $days       = (int) ($request->input('days', 30));

        // Aggregate stats per campaign (last N days)
        $campaigns = Campaign::query()
            ->when($collection, fn ($q) => $q->where('collection', $collection))
            ->whereIn('status', ['sent', 'sending', 'partial'])
            ->where('sent_at', '>=', now()->subDays($days))
            ->withCount([
                'sends',
                'sends as delivered_count' => fn ($q) => $q->whereIn('status', ['delivered', 'opened', 'clicked']),
                'sends as opened_count'    => fn ($q) => $q->whereNotNull('opened_at'),
                'sends as clicked_count'   => fn ($q) => $q->whereNotNull('clicked_at'),
                'sends as bounced_count'   => fn ($q) => $q->whereIn('status', ['bounced', 'failed']),
            ])
            ->orderByDesc('sent_at')
            ->get();

        // Totals across the period
        $totals = [
            'campaigns'  => $campaigns->count(),
            'sent'        => $campaigns->sum('sends_count'),
            'delivered'   => $campaigns->sum('delivered_count'),
            'opened'      => $campaigns->sum('opened_count'),
            'clicked'     => $campaigns->sum('clicked_count'),
            'bounced'     => $campaigns->sum('bounced_count'),
        ];

        $totals['delivery_rate'] = $totals['sent'] > 0
            ? round(($totals['delivered'] / $totals['sent']) * 100, 1) : 0;
        $totals['open_rate'] = $totals['delivered'] > 0
            ? round(($totals['opened'] / $totals['delivered']) * 100, 1) : 0;
        $totals['click_rate'] = $totals['opened'] > 0
            ? round(($totals['clicked'] / $totals['opened']) * 100, 1) : 0;

        // Daily send volume for chart (last N days)
        $dailyVolume = CampaignSend::query()
            ->selectRaw('DATE(sent_at) as date, COUNT(*) as total, SUM(opened_at IS NOT NULL) as opened')
            ->where('sent_at', '>=', now()->subDays($days))
            ->when($collection, fn ($q) => $q->whereHas('campaign', fn ($cq) => $cq->where('collection', $collection)))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        // Webhook health (last 24h)
        $webhookHealth = [
            'total'       => WebhookLog::where('created_at', '>=', now()->subHours(24))->count(),
            'processed'   => WebhookLog::where('created_at', '>=', now()->subHours(24))->whereNotNull('processed_at')->count(),
            'failed'      => WebhookLog::where('created_at', '>=', now()->subHours(24))->whereNotNull('error')->count(),
            'pending'     => WebhookLog::where('created_at', '>=', now()->subHours(24))->whereNull('processed_at')->whereNull('error')->count(),
        ];

        $collections = $this->collectionOptions();

        return view('newsletter.cp.analytics.index', compact(
            'campaigns', 'totals', 'dailyVolume', 'webhookHealth', 'days', 'collection', 'collections'
        ));
    }

    protected function collectionOptions(): array
    {
        return app(CollectionRegistry::class)->options();
    }

    /* ------------------------------------------------------------------ */
    /* Per-campaign analytics detail                                        */
    /* ------------------------------------------------------------------ */

    public function campaign(Campaign $campaign)
    {
        $campaign->load('audiences.targetable');
        $data = $this->campaignAnalyticsData($campaign);

        return view('newsletter.cp.analytics.campaign', array_merge([
            'campaign' => $campaign,
        ], $data));
    }

    public function campaignStatus(Campaign $campaign)
    {
        $campaign->load('audiences.targetable');
        $data = $this->campaignAnalyticsData($campaign);
        $stats = $data['stats'];

        return response()->json([
            'sync' => $this->syncPayload($campaign->fresh()),
            'metrics' => [
                'total_sent' => (int) ($stats['total_sent'] ?? 0),
                'delivered' => (int) ($stats['delivered'] ?? 0),
                'opened' => (int) ($stats['opened'] ?? 0),
                'clicked' => (int) ($stats['clicked'] ?? 0),
                'bounced' => (int) ($stats['bounced'] ?? 0),
                'failed' => (int) ($stats['failed'] ?? 0),
                'complained' => (int) ($stats['complained'] ?? 0),
                'unread' => (int) ($stats['unread'] ?? 0),
                'delivery_rate' => (float) ($stats['delivery_rate'] ?? 0),
                'open_rate' => (float) ($stats['open_rate'] ?? 0),
                'click_rate' => (float) ($stats['click_rate'] ?? 0),
                'click_to_delivery' => (float) ($stats['click_to_delivery'] ?? 0),
            ],
            'status_breakdown' => $this->statusBreakdownRows($stats),
            'opens_over_time' => $data['opensOverTime']->toArray(),
            'opens_by_hour' => $data['opensByHour']->toArray(),
            'top_links' => $data['topLinks']->map(fn ($link) => [
                'url' => $link->url,
                'clicks' => (int) $link->clicks,
                'unique_clicks' => (int) $link->unique_clicks,
            ])->values()->all(),
            'failed_sends_preview' => $data['failedSends']->map(fn ($send) => [
                'subscriber' => $send->subscriber?->email,
                'status' => $send->status,
                'reason' => $send->bounce_reason,
                'date' => ($send->bounced_at ?? $send->failed_at)?->format('M j H:i'),
            ])->values()->all(),
        ]);
    }

    public function syncCampaign(Campaign $campaign)
    {
        abort_if(! in_array($campaign->status, ['sending', 'sent', 'partial', 'failed']), 403);

        $campaign->forceFill([
            'last_stats_sync_requested_at' => now(),
            'last_stats_sync_completed_at' => null,
            'last_stats_sync_status' => 'queued',
            'last_stats_sync_total' => 0,
            'last_stats_sync_processed' => 0,
            'last_stats_sync_error' => null,
        ])->save();

        SyncCampaignStatsJob::dispatch($campaign->id)->onQueue('campaigns');

        return redirect(cp_route('newsletter.analytics.campaign', $campaign))
            ->with('success', 'Stats sync queued. Refresh in a moment.');
    }

    private function campaignAnalyticsData(Campaign $campaign): array
    {
        $stats = $campaign->sends()
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(status IN ("delivered","opened","clicked")) as delivered,
                SUM(status IN ("failed","bounced","complained")) as failed,
                SUM(opened_at IS NOT NULL) as opened,
                SUM(clicked_at IS NOT NULL) as clicked,
                SUM(status = "bounced") as bounced,
                SUM(status = "complained") as complained,
                SUM(status IN ("delivered","opened","clicked") AND opened_at IS NULL) as unread
            ')
            ->first()
            ->toArray();

        $stats['delivery_rate'] = $stats['total_sent'] > 0
            ? round(($stats['delivered'] / $stats['total_sent']) * 100, 1) : 0;
        $stats['open_rate'] = $stats['delivered'] > 0
            ? round(($stats['opened'] / $stats['delivered']) * 100, 1) : 0;
        $stats['click_rate'] = $stats['opened'] > 0
            ? round(($stats['clicked'] / $stats['opened']) * 100, 1) : 0;
        $stats['click_to_delivery'] = $stats['delivered'] > 0
            ? round(($stats['clicked'] / $stats['delivered']) * 100, 1) : 0;

        $opensByHour = $campaign->sends()
            ->selectRaw('HOUR(opened_at) as hour, COUNT(*) as count')
            ->whereNotNull('opened_at')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        $topLinks = CampaignLinkClick::query()
            ->whereHas('campaignSend', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->selectRaw('url, COUNT(*) as clicks, COUNT(DISTINCT campaign_send_id) as unique_clicks')
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(15)
            ->get();

        $statusBreakdown = $campaign->sends()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $opensOverTime = collect();
        if ($campaign->sent_at) {
            $opensOverTime = $campaign->sends()
                ->selectRaw('TIMESTAMPDIFF(HOUR, ?, opened_at) as hours_after, COUNT(*) as count', [$campaign->sent_at])
                ->whereNotNull('opened_at')
                ->where('opened_at', '<=', $campaign->sent_at->copy()->addHours(48))
                ->groupBy('hours_after')
                ->orderBy('hours_after')
                ->pluck('count', 'hours_after');
        }

        $failedSends = $campaign->sends()
            ->whereIn('status', ['failed', 'bounced'])
            ->with('subscriber:id,email,first_name,last_name')
            ->limit(50)
            ->get(['id', 'subscriber_id', 'status', 'bounce_reason', 'failed_at', 'bounced_at']);

        return compact('stats', 'opensByHour', 'topLinks', 'statusBreakdown', 'opensOverTime', 'failedSends');
    }

    private function statusBreakdownRows(array $stats): array
    {
        $total = max(1, (int) ($stats['total_sent'] ?? 0));

        return [
            ['key' => 'clicked', 'label' => 'Clicked', 'count' => (int) ($stats['clicked'] ?? 0), 'percent' => round(((int) ($stats['clicked'] ?? 0) / $total) * 100, 1)],
            ['key' => 'opened', 'label' => 'Opened', 'count' => (int) ($stats['opened'] ?? 0), 'percent' => round(((int) ($stats['opened'] ?? 0) / $total) * 100, 1)],
            ['key' => 'unread', 'label' => 'Unread', 'count' => (int) ($stats['unread'] ?? 0), 'percent' => round(((int) ($stats['unread'] ?? 0) / $total) * 100, 1)],
            ['key' => 'bounced', 'label' => 'Bounced', 'count' => (int) ($stats['bounced'] ?? 0), 'percent' => round(((int) ($stats['bounced'] ?? 0) / $total) * 100, 1)],
            ['key' => 'failed', 'label' => 'Failed', 'count' => (int) ($stats['failed'] ?? 0), 'percent' => round(((int) ($stats['failed'] ?? 0) / $total) * 100, 1)],
            ['key' => 'complained', 'label' => 'Complained', 'count' => (int) ($stats['complained'] ?? 0), 'percent' => round(((int) ($stats['complained'] ?? 0) / $total) * 100, 1)],
        ];
    }

    private function syncPayload(Campaign $campaign): array
    {
        return [
            'status' => $campaign->last_stats_sync_status,
            'requested_at' => optional($campaign->last_stats_sync_requested_at)?->toIso8601String(),
            'completed_at' => optional($campaign->last_stats_sync_completed_at)?->toIso8601String(),
            'total' => (int) ($campaign->last_stats_sync_total ?? 0),
            'processed' => (int) ($campaign->last_stats_sync_processed ?? 0),
            'progress_percent' => $campaign->statsSyncProgress(),
            'error' => $campaign->last_stats_sync_error,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Webhook log viewer                                                   */
    /* ------------------------------------------------------------------ */

    public function webhooks(Request $request)
    {
        $query = WebhookLog::query()->latest();

        if ($type = $request->input('event_type')) {
            $query->where('event_type', $type);
        }

        if ($request->input('failed')) {
            $query->failed();
        }

        $logs = $query->paginate(50)->withQueryString();

        $eventTypes = WebhookLog::select('event_type')
            ->distinct()
            ->whereNotNull('event_type')
            ->orderBy('event_type')
            ->pluck('event_type');

        return view('newsletter.cp.analytics.webhooks', compact('logs', 'eventTypes'));
    }
}
