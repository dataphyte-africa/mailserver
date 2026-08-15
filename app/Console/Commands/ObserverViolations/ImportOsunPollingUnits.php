<?php

namespace App\Console\Commands\ObserverViolations;

use App\Services\ObserverViolations\OsunPollingUnitImportService;
use Illuminate\Console\Command;

class ImportOsunPollingUnits extends Command
{
    protected $signature = 'observer-violations:import-osun-polling-units
        {--path= : Path to the Osun polling unit CSV source}';

    protected $description = 'Import the Osun polling-unit mapping for observer violation intake.';

    public function handle(OsunPollingUnitImportService $service): int
    {
        $path = (string) ($this->option('path') ?: config('observer_violations.osun_polling_units_import_path'));
        $result = $service->import($path);

        $this->info(sprintf(
            'Imported %d polling units (%d inserted, %d updated).',
            $result['total_rows'],
            $result['inserted'],
            $result['updated'],
        ));

        return self::SUCCESS;
    }
}
