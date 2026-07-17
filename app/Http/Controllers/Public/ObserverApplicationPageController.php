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

        $osun = $this->locations->findStateByName('Osun') ?: ['id' => 31, 'name' => 'Osun'];
        $foundationLogoUrl = $this->logoUrl(config('statamic.cp.custom_logo_url'));
        $closedAt = $this->forms->closedAt($resolved);

        return response()->view('newsletter.public.observer-application', [
            'form' => $resolved,
            'schemaEndpoint' => route('newsletter.forms.schema', ['form' => $form]),
            'submitEndpoint' => route('newsletter.forms.submit', ['form' => $form]),
            'statesEndpoint' => route('newsletter.forms.locations.states', ['form' => $form]),
            'lgasEndpointTemplate' => route('newsletter.forms.locations.lgas', ['form' => $form, 'state' => '__STATE__']),
            'wardsEndpointTemplate' => route('newsletter.forms.locations.wards', ['form' => $form, 'lga' => '__LGA__']),
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
