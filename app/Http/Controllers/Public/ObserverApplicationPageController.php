<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Services\Foundation\ElectionLocationService;
use App\Services\Newsletter\SubscriptionFormService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

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
        if ($this->forms->submissionMode($resolved) === 'subscription') {
            $organisation = Organisation::query()
                ->where('primary_collection_handle', $this->forms->collectionHandle($resolved))
                ->first();

            return response()->view('newsletter.public.legacy-subscribe', [
                'form' => $resolved,
                'schema' => $this->forms->schema($resolved),
                'organisation' => $organisation,
                'displayName' => $organisation?->name ?: Str::headline($resolved->title()),
                'submitEndpoint' => route('newsletter.forms.submit', ['form' => $this->forms->endpointSlug($resolved)]),
            ])->header('Cache-Control', 'no-store');
        }

        $publicLinks = $this->forms->publicFormLinks($resolved);
        $osun = $this->locations->findStateByName('Osun') ?: ['id' => 31, 'name' => 'Osun'];
        $foundationLogoUrl = $this->logoUrl(config('statamic.cp.custom_logo_url'));
        $closedAt = $this->forms->closedAt($resolved);

        if ($closedAt && now()->greaterThan($closedAt)) {
            return response()->view('forms.public.closed', [
                'title' => $resolved->title(),
                'brandName' => 'Dataphyte Foundation',
                'logoUrl' => $foundationLogoUrl,
                'closesAt' => $closedAt,
                'message' => $this->forms->closedMessage($resolved),
            ])->header('Cache-Control', 'no-store');
        }

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
        ])->header('Cache-Control', 'no-store')
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
