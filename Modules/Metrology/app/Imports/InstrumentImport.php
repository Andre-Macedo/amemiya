<?php

namespace Modules\Metrology\Imports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\Supplier;

class InstrumentImport implements ToModel, WithHeadingRow
{
    /**
     * @param array<string, mixed> $row
     * @return Model|null
     */
    public function model(array $row): ?Model
    {
        if (empty($row['name'])) {
            return null;
        }

        $manufacturerName = $row['manufacturer'] ?? $row['fabricante'] ?? null;
        $supplierId = null;

        if (! empty($manufacturerName)) {
            $supplier = Supplier::query()
                ->where('name', 'like', trim((string) $manufacturerName))
                ->first();
            $supplierId = $supplier?->id;
        }

        return new Instrument([
            'name' => $row['name'],
            'serial_number' => $row['serial_number'] ?? $row['numero_serie'] ?? null,
            'stock_number' => $row['stock_number'] ?? $row['patrimonio'] ?? null,
            'manufacturer' => $manufacturerName,
            'current_supplier_id' => $supplierId,
            'model' => $row['model'] ?? $row['modelo'] ?? null,
            'measuring_range' => $row['range'] ?? $row['measuring_range'] ?? $row['faixa'] ?? null,
            'resolution' => $row['resolution'] ?? $row['resolucao'] ?? null,
            'status' => 'active',
            'acquisition_date' => isset($row['acquisition_date']) ? Carbon::parse($row['acquisition_date']) : now(),
            'calibration_due' => isset($row['calibration_due']) ? Carbon::parse($row['calibration_due']) : now()->addYear(),
        ]);
    }
}
