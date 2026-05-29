<?php

namespace Modules\Metrology\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Metrology\Models\Calibration;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CalibrationsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = Calibration::query()->with(['calibratedItem', 'performedBy', 'provider']);

        if (! empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['result']) && $this->filters['status'] !== 'all') {
            $query->where('result', $this->filters['result']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Certificate #',
            'Date',
            'Instrument',
            'S/N',
            'Technician',
            'Laboratory',
            'Type',
            'Result',
            'Deviation',
            'Uncertainty (U)',
            'Status',
        ];
    }

    public function map($cal): array
    {
        return [
            $cal->id,
            $cal->certificate_number,
            $cal->calibration_date->format('d/m/Y'),
            $cal->calibratedItem?->name ?? 'N/A',
            $cal->calibratedItem?->serial_number ?? 'N/A',
            $cal->performedBy?->name ?? 'N/A',
            $cal->provider?->name ?? 'Internal',
            strtoupper($cal->type),
            strtoupper($cal->result),
            $cal->deviation,
            $cal->uncertainty,
            strtoupper($cal->status),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }
}
