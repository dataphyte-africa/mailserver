<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Foundation\ElectionLocationService;
use App\Services\Newsletter\SubscriptionFormService;

class ElectionLocationController extends Controller
{
    public function __construct(
        private readonly ElectionLocationService $locations,
        private readonly SubscriptionFormService $forms,
    ) {}

    public function states(string $form)
    {
        abort_if(! $this->forms->resolveForm($form), 404);

        return response()->json($this->locations->states());
    }

    public function lgas(string $form, int $state)
    {
        abort_if(! $this->forms->resolveForm($form), 404);

        return response()->json($this->locations->lgas($state));
    }

    public function wards(string $form, int $lga)
    {
        abort_if(! $this->forms->resolveForm($form), 404);

        return response()->json($this->locations->wards($lga));
    }
}
