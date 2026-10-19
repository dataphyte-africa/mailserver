<?php

namespace App\Services\ObserverViolations;

use App\Models\OsunPollingUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class OsunPollingUnitImportService
{
    public function import(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException("Polling unit import file not found: {$path}");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $header = null;
        $rows = [];

        foreach ($file as $row) {
            if (! is_array($row) || $row === [null]) {
                continue;
            }

            if ($header === null) {
                $header = array_map(static fn ($value) => trim((string) $value), $row);
                continue;
            }

            $mapped = $this->mapRow($header, $row);

            if ($mapped === null) {
                continue;
            }

            $rows[] = $mapped;
        }

        if ($header === null) {
            throw new RuntimeException('Polling unit import file is empty.');
        }

        $codes = collect($rows)->pluck('polling_unit_code')->filter()->values();
        $existingCodes = OsunPollingUnit::query()
            ->whereIn('polling_unit_code', $codes)
            ->pluck('polling_unit_code');

        DB::transaction(function () use ($rows): void {
            foreach (array_chunk($rows, 500) as $chunk) {
                OsunPollingUnit::query()->upsert(
                    $chunk,
                    ['polling_unit_code'],
                    ['state', 'lga', 'ward', 'polling_unit_name', 'source_url', 'updated_at']
                );
            }
        });

        return [
            'total_rows' => count($rows),
            'inserted' => $codes->diff($existingCodes)->count(),
            'updated' => $existingCodes->intersect($codes)->count(),
        ];
    }

    public function lgas(): Collection
    {
        return OsunPollingUnit::query()
            ->select('lga')
            ->distinct()
            ->orderBy('lga')
            ->pluck('lga');
    }

    public function wards(string $lga): Collection
    {
        return OsunPollingUnit::query()
            ->where('lga', $lga)
            ->select('ward')
            ->distinct()
            ->orderBy('ward')
            ->pluck('ward');
    }

    public function pollingUnits(string $lga, string $ward): Collection
    {
        return OsunPollingUnit::query()
            ->where('lga', $lga)
            ->where('ward', $ward)
            ->orderBy('polling_unit_name')
            ->get(['polling_unit_code', 'polling_unit_name']);
    }

    private function mapRow(array $header, array $row): ?array
    {
        $values = [];

        foreach ($header as $index => $column) {
            $values[$column] = trim((string) ($row[$index] ?? ''));
        }

        if (($values['polling_unit_code'] ?? '') === '') {
            return null;
        }

        return [
            'state' => $values['state'] ?? 'Osun',
            'lga' => $values['lga'] ?? '',
            'ward' => $values['ward'] ?? '',
            'polling_unit_code' => $values['polling_unit_code'],
            'polling_unit_name' => $values['polling_unit_name'] ?? '',
            'source_url' => $values['source_url'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
