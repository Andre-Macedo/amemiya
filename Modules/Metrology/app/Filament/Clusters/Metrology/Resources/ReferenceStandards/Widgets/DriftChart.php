<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\ReferenceStandards\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Model;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;

class DriftChart extends ChartWidget
{
    protected ?string $heading = 'Monitoramento de Deriva (Histórico de Calibrações)';
    
    // Filament passes the current record to widgets on View/Edit pages if configured correcty
    public ?Model $record = null;

    protected function getData(): array
    {
        if (! $this->record) {
            return [];
        }

        // Fetch calibrations for this ReferenceStandard
        $calibrations = Calibration::query()
            ->where('calibrated_item_type', ReferenceStandard::class)
            ->where('calibrated_item_id', $this->record->id)
            ->where('result', 'approved') // Only approved
            ->orderBy('calibration_date')
            ->get();

        // Datasets logic
        // We need to plot the Deviation (Bias) or the Actual Value.
        // If we have multiple points (like a Kit), summarizing in one chart is hard.
        // Assuming simple standard (one nominal) or just taking the average bias of all points?
        // Better: Plot the "Max Deviation" found in that calibration, or if single point, the deviation.
        
        $data = [];
        $labels = [];

        foreach ($calibrations as $cal) {
            $labels[] = $cal->calibration_date->format('d/m/Y');
            
            // If the calibration has stored deviation in the table (from our logic)
            // convert to float.
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
