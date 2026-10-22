<?php

namespace App\Http\Controllers\Public;

use App\Mail\NewsletterMailable;
use App\Models\Campaign;
use App\Models\Organisation;
use App\Models\Product;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class NewsletterArchiveController
{
    public function index(Organisation $organisation, string $collection, ?Product $product = null): View
    {
        abort_unless($organisation->primary_collection_handle === $collection, 404);
        abort_if($product && ($product->organisation_id !== $organisation->id || $product->primary_collection_handle !== $collection), 404);

        $issues = Campaign::query()
            ->where('organisation_id', $organisation->id)
            ->where('collection', $collection)
            ->where('product_id', $product?->id)
            ->whereIn('status', ['sent', 'partial'])
            ->whereNotNull('sent_at')
            ->latest('sent_at')
            ->paginate(20);

        return view('newsletter.public.archive', compact('organisation', 'product', 'issues'));
    }

    public function show(Campaign $campaign): Response
    {
        $hasStartedDelivery = $campaign->status === 'sending'
            && $campaign->sends()->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])->exists();
        abort_unless((in_array($campaign->status, ['sent', 'partial'], true) || $hasStartedDelivery)
            && $campaign->sent_at && $campaign->entry_id, 404);
        abort_unless($campaign->organisation && $campaign->organisation->primary_collection_handle === $campaign->collection, 404);

        $mailable = new NewsletterMailable($campaign, new Subscriber(['email' => '']), 'archive', true);
        abort_unless($mailable->hasEntry(), 404);

        return response($mailable->render())->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testPreview(Request $request, Campaign $campaign): Response
    {
        abort_unless($request->hasValidSignature(false), 403);
        abort_unless($campaign->entry_id && $campaign->organisation, 404);

        $mailable = new NewsletterMailable($campaign, new Subscriber([
            'email' => '',
            'first_name' => 'Test',
            'last_name' => 'Recipient',
        ]), 'test-preview', false, true);
        abort_unless($mailable->hasEntry(), 404);

        return response($mailable->render())->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function testAction(Request $request, Campaign $campaign, string $action): View
    {
        abort_unless($request->hasValidSignature(false), 403);

        return view('newsletter.public.test-action', compact('campaign', 'action'));
    }
}
