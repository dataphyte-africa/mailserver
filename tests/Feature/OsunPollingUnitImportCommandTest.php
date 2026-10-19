<?php

namespace Tests\Feature;

use App\Models\OsunPollingUnit;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class OsunPollingUnitImportCommandTest extends TestCase
{
    public function test_it_imports_polling_units_and_exposes_filtered_lookup_endpoints(): void
    {
        $fixturePath = storage_path('framework/testing/osun-polling-units.csv');

        File::ensureDirectoryExists(dirname($fixturePath));
        File::put($fixturePath, implode("\n", [
            'state,lga,ward,polling_unit_code,polling_unit_name,source_url',
            'Osun,Osogbo,Akepe / Eketa,29/30/01/016,1 ASUBIARO STR,https://example.com/1',
            'Osun,Osogbo,Akepe / Eketa,29/30/01/017,1 ABABU STREET,https://example.com/2',
            'Osun,Ede North,Sabo Agbengbe 1,29/07/09/003,1 AYOPE COMP.,https://example.com/3',
        ]));

        $this->artisan('observer-violations:import-osun-polling-units', ['--path' => $fixturePath])
            ->assertSuccessful()
            ->expectsOutput('Imported 3 polling units (3 inserted, 0 updated).');

        $this->assertSame(3, OsunPollingUnit::query()->count());

        $this->getJson(route('observer-violations.locations.lgas'))
            ->assertOk()
            ->assertJsonPath('data.0', 'Ede North')
            ->assertJsonPath('data.1', 'Osogbo');

        $this->getJson(route('observer-violations.locations.wards', ['lga' => 'Osogbo']))
            ->assertOk()
            ->assertJsonPath('data.0', 'Akepe / Eketa');

        $this->getJson(route('observer-violations.locations.polling-units', [
            'lga' => 'Osogbo',
            'ward' => 'Akepe / Eketa',
        ]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.polling_unit_code', '29/30/01/017');
    }
}
