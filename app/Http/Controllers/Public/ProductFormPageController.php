<?php

namespace App\Http\Controllers\Public;

use App\Services\Forms\ApplicationWindow;
use App\Services\Forms\ProductFormService;
use App\Services\Forms\ProductFormSubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ProductFormPageController
{
    public function __construct(
        private readonly ProductFormService $forms,
    ) {}

    public function show(string $form, ProductFormSubscriptionService $subscriptions, ApplicationWindow $window): View
    {
        $resolved = $this->forms->resolvePublishedForm($form);

        abort_unless($resolved?->isPublished(), 404);

        if ($resolved->mode !== 'subscription' && ! $window->isOpen(
            (bool) ($resolved->settings['accepting_submissions'] ?? true),
            $resolved->settings['closes_at'] ?? null,
        )) {
            $organisation = $resolved->organisation;

            return view('forms.public.closed', [
                'title' => $resolved->name,
                'brandName' => $organisation?->name ?? 'Dataphyte Foundation',
                'logoUrl' => $organisation?->logo_path ? Storage::disk('public')->url($organisation->logo_path) : null,
                'closesAt' => $window->closesAt($resolved->settings['closes_at'] ?? null),
            ]);
        }

        return view('forms.public.show', [
            'form' => $resolved->loadMissing(['product.organisation']),
            'hostedPageUrl' => $this->forms->hostedPageUrl($resolved),
            'schemaUrl' => $this->forms->schemaUrl($resolved),
            'submitUrl' => $this->forms->submitUrl($resolved),
            'publicPreferences' => $resolved->mode === 'subscription'
                ? $subscriptions->approvedPreferences($resolved) : collect(),
        ]);
    }

    public function schema(string $form): JsonResponse
    {
        $resolved = $this->forms->resolvePublishedForm($form);

        abort_unless($resolved?->isPublished(), 404);

        return response()->json($this->forms->publicSchema($resolved));
    }
}
