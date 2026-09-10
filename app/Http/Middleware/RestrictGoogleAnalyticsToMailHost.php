<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class RestrictGoogleAnalyticsToMailHost
{
    /**
     * Disable the configured Google tag on every hostname except the newsletter host.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (strtolower($request->getHost()) !== 'mail.dataphyte.com') {
            Config::set('services.google_analytics.measurement_id', null);
        }

        return $next($request);
    }
}
