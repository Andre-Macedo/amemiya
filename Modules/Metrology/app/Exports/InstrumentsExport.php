<?php

namespace Modules\Metrology\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Metrology\Models\Instrument;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstrumentsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    /**
     * Query the instruments based on active filters.
     */
    public function query()
    {
        $query = Instrument::query()->with(['instrumentType', 'station']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('stock_number', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        return $query;
    }

    /**
     * Define the headers of the Excel file.
     */
    public function headings(): array
    {
        return [
            'ID',
            'Instrument Name',
            'Serial Number',
            'Stock Number (TAG)',
            'Manufacturer',
            'Model',
            'Type',
            'Status',
            'Last Calibration',
            'Next Calibration Due',
            'Location / Station',
            'Created At',
        ];
    }

    /**
     * Map data for each row.
     */
    public function map($instrument): array
    {
        return [
            $instrument->id,
            $instrument->name,
            $instrument->serial_number,
            $instrument->stock_number,
            $instrument->manufacturer_name, // Assuming accessor exists or direct field
            $instrument->model,
            $instrument->instrumentType?->name ?? 'N/A',
            strtoupper($instrument->status),
            $instrument->last_calibration_date ? $instrument->last_calibration_date->format('d/m/Y') : 'N/A',
            $instrument->next_calibration_date ? $instrument->next_calibration_date->format('d/m/Y') : 'N/A',
            $instrument->station?->name ?? 'Unassigned',
            $instrument->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Apply basic styling to the header.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }
}
