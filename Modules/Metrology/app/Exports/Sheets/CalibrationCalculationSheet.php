<?php

declare(strict_types=1);

namespace Modules\Metrology\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Metrology\Models\Calibration;

class CalibrationCalculationSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        protected Calibration $calibration
    ) {}

    /**
     * Gera a coleção de dados da memória de cálculo (ISO GUM).
     *
     * @return Collection
     */
    public function collection()
    {
        $rows = collect();
        $budget = $this->calibration->calculation_data;

        if (! is_array($budget)) {
            return collect([['Não há memória de cálculo salva para esta calibração.']]);
        }

        foreach ($budget as $component) {
            $rows->push([
                'source' => $component['source'] ?? 'N/A',
                'type' => $component['type'] ?? 'N/A',
                'description' => $component['description'] ?? '',
                'value' => $component['uncertainty_value'] ?? 0,
                'distribution' => $component['probability_distribution'] ?? '',
                'divisor' => $component['divisor'] ?? 1,
                'sensitivity' => $component['sensitivity_coefficient'] ?? 1,
                'standard_uncertainty' => $component['standard_uncertainty'] ?? 0,
                'degrees_of_freedom' => $component['degrees_of_freedom'] ?? 'inf',
            ]);
        }

        return $rows;
    }

    /**
     * Cabeçalhos da planilha.
     */
    public function headings(): array
    {
        return [
            'Fonte de Incerteza',
            'Tipo (A/B)',
            'Descrição',
            'Valor de Entrada',
            'Distribuição',
            'Divisor',
            'Ci (Sensibilidade)',
            'u(xi) (Inc. Padrão)',
            'Graus de Liberdade (Veff)',
        ];
    }

    /**
     * Título da aba.
     */
    public function title(): string
    {
        return 'Memorial de Cálculo (ISO GUM)';
    }
}
