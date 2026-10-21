<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Newsletter\NewsletterPublicUrlService;
use App\Services\Newsletter\GroupEngagementService;
use Illuminate\Http\Request;

class UnsubscribeController extends Controller
{
    public function show(Request $request, string $token, NewsletterPublicUrlService $urls)
    {
        return app(PreferencesController::class)->show($request, $token, $urls, 'unsubscribe');
    }

    public function process(Request $request, string $token, NewsletterPublicUrlService $urls, GroupEngagementService $engagement)
    {
        return app(PreferencesController::class)->update($request, $token, $urls, $engagement);
    }
}
