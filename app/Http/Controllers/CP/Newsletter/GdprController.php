<?php

namespace App\Http\Controllers\CP\Newsletter;

use App\Http\Controllers\Controller;
use App\Models\CampaignLinkClick;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * GDPR / right-to-data compliance actions.
 *
 * export()  — Download all personal data held for a subscriber (JSON).
 * erase()   — Anonymise a subscriber record (right to erasure / right to be forgotten).
 *             Campaign sends are kept for statistical integrity but de-linked from
 *             any personally identifiable information.
 */
class GdprController extends Controller
{
    /* ------------------------------------------------------------------ */
    /* Export                                                               */
    /* ------------------------------------------------------------------ */

    public function export(Subscriber $subscriber)
    {
        $totalLinkClicks = CampaignLinkClick::query()
            ->whereHas('campaignSend', fn ($q) => $q->where('subscriber_id', $subscriber->id))
            ->count();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'profile'     => [
                'email'            => $subscriber->email,
                'first_name'       => $subscriber->first_name,
                'last_name'        => $subscriber->last_name,
                'status'           => $subscriber->status,
                'engagement_rating'=> $subscriber->engagement_rating,
                'engagement_score' => $subscriber->engagement_score,
                'last_engaged_at'  => $subscriber->last_engaged_at?->toIso8601String(),
                'ip_address'       => $subscriber->ip_address,
                'confirmed_at'     => $subscriber->confirmed_at?->toIso8601String(),
                'unsubscribed_at'  => $subscriber->unsubscribed_at?->toIso8601String(),
                'created_at'       => $subscriber->created_at->toIso8601String(),
            ],
            'subscriptions' => $subscriber->allSubGroups()
                ->with('group:id,name,slug')
                ->get()
                ->map(fn ($sub) => [
                    'group'          => $sub->group->name ?? null,
                    'sub_group'      => $sub->name,
                    'subscribed_at'  => $sub->pivot->subscribed_at,
                    'unsubscribed_at'=> $sub->pivot->unsubscribed_at,
                ]),
            'campaign_history' => $subscriber->campaignSends()
                ->with('campaign:id,name,collection,subject,sent_at')
                ->orderByDesc('sent_at')
                ->get()
                ->map(fn ($send) => [
                    'campaign'    => $send->campaign?->name,
                    'collection'  => $send->campaign?->collection,
                    'subject'     => $send->campaign?->subject,
                    'sent_at'     => $send->sent_at?->toIso8601String(),
                    'status'      => $send->status,
                    'opened_at'   => $send->opened_at?->toIso8601String(),
                    'clicked_at'  => $send->clicked_at?->toIso8601String(),
                ]),
            'engagement_totals' => [
                'campaigns' => $subscriber->campaignSends()->count(),
                'delivered' => $subscriber->campaignSends()->whereIn('status', ['delivered', 'opened', 'clicked'])->count(),
                'failed' => $subscriber->campaignSends()->whereIn('status', ['failed', 'bounced'])->count(),
                'opened' => $subscriber->campaignSends()->whereNotNull('opened_at')->count(),
                'clicked' => $subscriber->campaignSends()->whereNotNull('clicked_at')->count(),
                'links_clicked' => $totalLinkClicks,
            ],
            'recent_clicked_links' => CampaignLinkClick::query()
                ->whereHas('campaignSend', fn ($q) => $q->where('subscriber_id', $subscriber->id))
                ->with(['campaignSend.campaign:id,name'])
                ->latest('clicked_at')
                ->limit(20)
                ->get()
                ->map(fn ($click) => [
                    'campaign' => $click->campaignSend?->campaign?->name,
                    'clicked_at' => $click->clicked_at?->toIso8601String(),
                    'url' => $click->url,
                ]),
        ];

        $filename = 'subscriber-data-' . Str::slug($subscriber->email) . '-' . now()->format('Ymd') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /* Erase form                                                          */
    /* ------------------------------------------------------------------ */

    public function eraseForm(Subscriber $subscriber)
    {
        abort_if($subscriber->status === 'erased', 404);

        return view('newsletter.cp.subscribers.gdpr-erase', compact('subscriber'));
    }

    /* ------------------------------------------------------------------ */
    /* Erase (right to be forgotten)                                       */
    /* ------------------------------------------------------------------ */

    public function erase(Request $request, Subscriber $subscriber)
    {
        $request->validate([
            'confirm' => 'required|in:ERASE',
        ], [
            'confirm.in' => 'Type ERASE in the confirmation field to proceed.',
        ]);

        DB::transaction(function () use ($subscriber) {
            $anonymisedEmail = 'deleted-' . $subscriber->id . '@deleted.invalid';

            // Anonymise personal data — keep the row for FK integrity
            $subscriber->update([
                'email'              => $anonymisedEmail,
                'first_name'         => null,
                'last_name'          => null,
                'ip_address'         => null,
                'user_agent'         => null,
                'metadata'           => null,
                'confirmation_token' => null,
                'status'             => 'erased',
            ]);

            // Detach all sub-group memberships
            $subscriber->allSubGroups()->detach();
        });

        return redirect(cp_route('newsletter.subscribers.index'))
            ->with('success', "Subscriber #{$subscriber->id} data has been erased.");
    }
}
