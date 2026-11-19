<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CampaignLinkClick;
use App\Models\CampaignSend;
use App\Models\TorInvitation;
use App\Services\Mail\FirstPartyClickTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailClickTrackingController extends Controller
{
    public function __invoke(string $payload, Request $request, FirstPartyClickTrackingService $tracking): RedirectResponse
    {
        try {
            $click = $tracking->decode($payload);
        } catch (\Throwable) {
            abort(404);
        }

        if ($click['campaign_send_id']) {
            $send = CampaignSend::query()->find($click['campaign_send_id']);
            if ($send) {
                $at = now();
                $send->update([
                    'status' => in_array($send->status, ['queued', 'sent', 'delivered', 'opened'], true) ? 'clicked' : $send->status,
                    'clicked_at' => $send->clicked_at ?? $at,
                ]);
                CampaignLinkClick::query()->create([
                    'campaign_send_id' => $send->id,
                    'url' => $click['url'],
                    'clicked_at' => $at,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 65535),
                ]);
            }
        }

        if ($click['tor_invitation_id']) {
            $invitation = TorInvitation::query()->find($click['tor_invitation_id']);
            if ($invitation && ! $invitation->isComplete() && $invitation->status !== 'revoked') {
                $invitation->update([
                    'status' => 'clicked',
                    'clicked_at' => $invitation->clicked_at ?? now(),
                    'delivered_at' => $invitation->delivered_at ?? now(),
                ]);
            }
        }

        return redirect()->away($click['url']);
    }
}
