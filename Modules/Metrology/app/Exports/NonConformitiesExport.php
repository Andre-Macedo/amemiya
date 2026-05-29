<?php

namespace Modules\Metrology\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Modules\Metrology\Models\NonConformity;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class NonConformitiesExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    public function __construct(protected array $filters = []) {}

    public function query()
    {
        $query = NonConformity::query()->with(['item', 'opener', 'closer']);

        if (! empty($this->filters['status']) && $this->filters['status'] !== 'all') {
            $query->where('status', $this->filters['status']);
        }

        if (! empty($this->filters['priority']) && $this->filters['priority'] !== 'all') {
            $query->where('priority', $this->filters['priority']);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Item',
            'Status',
            'Priority',
            'Opened By',
            'Opened At',
            'Closed By',
            'Closed At',
            'Root Cause',
            'Corrective Action',
        ];
    }

    public function map($nc): array
    {
        return [
            $nc->id,
            $nc->title,
            $nc->item?->name ?? 'N/A',
            strtoupper($nc->status),
            strtoupper($nc->priority),
            $nc->opener?->name ?? 'System',
            $nc->created_at->format('d/m/Y H:i'),
            $nc->closer?->name ?? '-',
            $nc->closed_at ? $nc->closed_at->format('d/m/Y H:i') : '-',
            $nc->root_cause_analysis,
            $nc->corrective_action,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2E8F0']]],
        ];
    }
}
