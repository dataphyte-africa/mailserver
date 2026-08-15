<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\ObserverViolations\OsunPollingUnitImportService;
use Illuminate\Http\Request;

class OsunPollingUnitController extends Controller
{
    public function __construct(
        private readonly OsunPollingUnitImportService $pollingUnits,
    ) {}

    public function lgas()
    {
        return response()->json([
            'state' => 'Osun',
            'data' => $this->pollingUnits->lgas()->values()->all(),
        ]);
    }

    public function wards(Request $request)
    {
        $validated = $request->validate([
            'lga' => ['required', 'string'],
        ]);

        return response()->json([
            'state' => 'Osun',
            'lga' => $validated['lga'],
            'data' => $this->pollingUnits->wards($validated['lga'])->values()->all(),
        ]);
    }

    public function pollingUnits(Request $request)
    {
        $validated = $request->validate([
            'lga' => ['required', 'string'],
            'ward' => ['required', 'string'],
        ]);

        return response()->json([
            'state' => 'Osun',
            'lga' => $validated['lga'],
            'ward' => $validated['ward'],
            'data' => $this->pollingUnits->pollingUnits($validated['lga'], $validated['ward'])->all(),
        ]);
    }
}
