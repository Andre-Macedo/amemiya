<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;

/**
 * Widget de gráfico de linha mostrando a deriva (tendência) dos Padrões de Referência
 * baseada no histórico de calibrações.
 */
class DriftChart extends ChartWidget
{
    protected ?string $heading = 'Monitoramento de Deriva (Histórico de Calibrações)';

    // O Filament passa o registro atual para widgets nas páginas View/Edit se configurado corretamente
    public ?Model $record = null;

    protected function getData(): array
    {
        if (! $this->record) {
            return [];
        }

        // Busca calibrações para este Padrão de Referência
        $calibrations = Calibration::query()
            ->where('calibrated_item_type', ReferenceStandard::class)
            ->where('calibrated_item_id', $this->record->id)
            ->where('result', 'approved') // Apenas aprovados
            ->orderBy('calibration_date')
            ->get();

        // Lógica dos Datasets
        // Precisamos plotar o Desvio (Tendência).
        // Melhor abordagem: Plotar o "Desvio Máximo" encontrado naquela calibração.

        $data = [];
        $labels = [];

        foreach ($calibrations as $cal) {
            $labels[] = $cal->calibration_date->format('d/m/Y');

            // Se a calibração tem desvio salvo na tabela
            // converter para float.
            $data[] = (float) $cal->deviation;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Desvio (Tendência) [mm]',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
