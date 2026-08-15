<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;

class ObserverViolationTestPageController extends Controller
{
    public function __invoke()
    {
        return view('observer-violations.test', [
            'submitEndpoint' => route('observer-violations.reports.store'),
            'lgasEndpoint' => route('observer-violations.locations.lgas'),
            'wardsEndpoint' => route('observer-violations.locations.wards'),
            'pollingUnitsEndpoint' => route('observer-violations.locations.polling-units'),
            'violationCategory' => (string) config('observer_violations.violation_category'),
        ]);
    }
}
