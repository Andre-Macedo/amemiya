<?php

namespace Modules\Metrology\Imports;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Metrology\Models\Instrument;

class InstrumentImport implements ToModel, WithHeadingRow
{
    /**
     * @return Model|null
     */
    public function model(array $row)
    {
        return new Instrument([
            'name' => $row['name'],
            'serial_number' => $row['serial_number'] ?? null,
            'stock_number' => $row['stock_number'] ?? null,
            'manufacturer_id' => null, // TODO: Look up by manufacturer name
            'model' => $row['model'] ?? null,
            'measuring_range' => $row['range'] ?? null,
            'resolution' => $row['resolution'] ?? null,
            'calibration_frequency' => $row['calibration_frequency'] ?? 12,
            'status' => 'active',
        ]);
    }
}
