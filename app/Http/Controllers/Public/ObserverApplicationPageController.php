<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Foundation\ElectionLocationService;
use App\Services\Newsletter\SubscriptionFormService;
use Illuminate\Http\Response;

class ObserverApplicationPageController extends Controller
{
    public function __construct(
        private readonly SubscriptionFormService $forms,
        private readonly ElectionLocationService $locations,
    ) {}

    public function show(string $form): Response
    {
        $resolved = $this->forms->resolveForm($form);

        abort_unless($resolved, 404);
        abort_unless($this->forms->submissionMode($resolved) === 'application', 404);

        $publicLinks = $this->forms->publicFormLinks($resolved);
        $osun = $this->locations->findStateByName('Osun') ?: ['id' => 31, 'name' => 'Osun'];
        $foundationLogoUrl = $this->logoUrl(config('statamic.cp.custom_logo_url'));
        $closedAt = $this->forms->closedAt($resolved);

        return response()->view('newsletter.public.observer-application', [
            'form' => $resolved,
            'schemaEndpoint' => $publicLinks['schema'],
            'submitEndpoint' => $publicLinks['submit'],
            'statesEndpoint' => $publicLinks['states'],
            'lgasEndpointTemplate' => $publicLinks['lgas_template'],
            'wardsEndpointTemplate' => $publicLinks['wards_template'],
            'turnstileSiteKey' => (string) config('services.turnstile.site_key', ''),
            'turnstileBypass' => $this->forms->shouldBypassTurnstile(),
            'closedAt' => $closedAt,
            'closedAtIso' => $closedAt?->toIso8601String(),
            'closedMessage' => $this->forms->closedMessage($resolved),
            'ineligibleMessage' => $this->forms->ineligibleMessage($resolved),
            'successMessage' => $this->forms->successMessage($resolved),
            'osunState' => $osun,
            'foundationLogoUrl' => $foundationLogoUrl,
        ])->header('Cache-Control', 'public, max-age=300, s-maxage=600, stale-while-revalidate=60, no-transform')
            ->header('Vary', 'Accept-Encoding');
    }

    private function logoUrl(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return asset('assets/foundation/dataphyte-foundation-logo.png');
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
