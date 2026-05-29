<?php

namespace Modules\Metrology\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Metrology\Models\ReferenceStandard;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReferenceStandardsExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = ReferenceStandard::query()->with(['parent']);

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        if (! empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Standard Name',
            'Serial Number',
            'Type',
            'Kit Parent',
            'Status',
            'Next Calibration Due',
            'Created At',
        ];
    }

    public function map($std): array
    {
        return [
            $std->id,
            $std->name,
            $std->serial_number,
            $std->type,
            $std->parent?->name ?? 'None',
            strtoupper($std->status),
            $std->next_calibration_date ? $std->next_calibration_date->format('d/m/Y') : 'N/A',
            $std->created_at->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }
}
