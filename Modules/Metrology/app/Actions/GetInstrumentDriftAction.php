<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Instrument;

/**
 * Calculates drift trends for a specific instrument.
 */
class GetInstrumentDriftAction
{
    /**
     * Executes the drift analysis.
     *
     * Args:
     *     instrument: The instrument to analyze.
     *     nominalValue: Optional specific nominal value to filter measurements.
     *
     * Returns:
     *     An array containing labels, MPE, datasets, and available nominal points.
     */
    public function execute(Instrument $instrument, ?string $nominalValue = null): array
    {
        $query = $instrument->calibrations()
            ->where('result', '!=', 'rejected')
            ->latest('calibration_date')
            ->limit(10);

        $calibrations = $query->get()->reverse();

        $labels = [];
        $dataError = [];
        $dataUncertainty = [];

        foreach ($calibrations as $calibration) {
            $labels[] = $calibration->calibration_date->format('d/m/Y');

            if ($nominalValue) {
                $item = $calibration->checklist->items()
                    ->where('nominal_value', $nominalValue)
                    ->first();

                if (! $item) {
                    $item = $calibration->checklist->items()
                        ->where('step', 'like', "%{$nominalValue}%")
                        ->first();
                }

                if ($item && ! empty($item->readings)) {
                    $readings = is_array($item->readings) ? $item->readings : json_decode($item->readings, true);
                    if (is_array($readings) && count($readings) > 0) {
                        $avg = array_sum($readings) / count($readings);
                        $error = $avg - (float) $nominalValue;
                    } else {
                        $error = 0;
                    }
                    $uncertainty = (float) $item->uncertainty;
                } else {
                    $error = 0;
                    $uncertainty = 0;
                }
            } else {
                $error = (float) $calibration->deviation;
                $uncertainty = (float) $calibration->uncertainty;
            }

            $dataError[] = $error;
            $dataUncertainty[] = $uncertainty;
        }

        return [
            'labels' => $labels,
            'mpe' => $instrument->getMaximumPermissibleError(),
            'datasets' => [
                [
                    'label' => 'Erro (mm)',
                    'data' => $dataError,
                    'borderColor' => '#3b82f6',
                    'fill' => false,
                ],
                [
                    'label' => 'Incerteza (U)',
                    'data' => $dataUncertainty,
                    'borderColor' => '#ef4444',
                    'borderDash' => [5, 5],
                    'fill' => false,
                ],
            ],
            'available_points' => $instrument->calibrations()
                ->latest()
                ->first()?->checklist?->items
                ?->pluck('nominal_value')
                ->filter()
                ->values() ?? [],
        ];
    }
}
