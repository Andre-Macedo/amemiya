<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\DTOs\MeasurementCalculationData;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\UncertaintyCalculator;

/**
 * Calculates expanded uncertainty and uncertainty budget for calibration items.
 */
class CalculateCalibrationUncertaintyAction
{
    public function __construct(
        protected UncertaintyCalculator $calculator
    ) {}

    /**
     * Executes uncertainty calculation across calibration items.
     *
     * @param array<string, mixed> $data
     * @return array{
     *     uncertainty: float,
     *     k_factor: float,
     *     uncertainty_budget: array<int, array<string, mixed>>,
     *     points: array<int, array<string, mixed>>
     * }
     */
    public function execute(array $data): array
    {
        $instrument = null;
        if (! empty($data['instrument_id'])) {
            $instrument = Instrument::find($data['instrument_id']);
        }

        $defaultResolution = $instrument && $instrument->resolution ? (float) $instrument->resolution : 0.01;
        $temperature = isset($data['temperature']) && $data['temperature'] !== '' ? (float) $data['temperature'] : null;

        $items = $data['items'] ?? [];
        if (empty($items)) {
            return [
                'uncertainty' => 0.0,
                'k_factor' => 2.0,
                'uncertainty_budget' => [],
                'points' => [],
            ];
        }

        $points = [];
        $maxUncertainty = 0.0;
        $maxKFactor = 2.0;
        $criticalBudget = [];

        foreach ($items as $index => $item) {
            $rawReadings = $item['as_found_readings'] ?? $item['readings'] ?? [];
            if (empty($rawReadings)) {
                continue;
            }

            $readings = array_map('floatval', is_array($rawReadings) ? $rawReadings : [$rawReadings]);
            $nominalValue = isset($item['nominal_value']) && $item['nominal_value'] !== ''
                ? (float) $item['nominal_value']
                : ($readings[0] ?? 0.0);

            $resolution = isset($item['resolution']) && $item['resolution'] !== ''
                ? (float) $item['resolution']
                : $defaultResolution;

            $standardId = $item['standard_id'] ?? $item['reference_standard_id'] ?? $data['standard_id'] ?? null;
            $standardActualValue = $nominalValue;
            $standardUncertainty = 0.001;
            $standardK = 2.0;

            if ($standardId) {
                $standard = ReferenceStandard::find($standardId);
                if ($standard) {
                    if ($standard->actual_value !== null && is_numeric($standard->actual_value)) {
                        $standardActualValue = (float) $standard->actual_value;
                    }
                    if ($standard->uncertainty !== null && is_numeric($standard->uncertainty)) {
                        $standardUncertainty = (float) $standard->uncertainty;
                    }
                }
            }

            $calcData = new MeasurementCalculationData(
                readings: $readings,
                resolution: $resolution,
                standardActualValue: $standardActualValue,
                standardUncertainty: $standardUncertainty,
                standardK: $standardK,
                temperature: $temperature
            );

            $result = $this->calculator->calculate($calcData);

            $pointResult = [
                'step' => $item['step'] ?? ($index + 1),
                'nominal_value' => $nominalValue,
                'bias' => $result->bias,
                'uncertainty' => $result->expandedUncertainty,
                'k_factor' => $result->kFactor,
                'effective_degrees_of_freedom' => $result->effectiveDegreesOfFreedom,
                'budget' => $result->budget,
            ];

            $points[] = $pointResult;

            if ($result->expandedUncertainty >= $maxUncertainty) {
                $maxUncertainty = $result->expandedUncertainty;
                $maxKFactor = $result->kFactor;
                $criticalBudget = $result->budget;
            }
        }

        return [
            'uncertainty' => $maxUncertainty,
            'k_factor' => $maxKFactor,
            'uncertainty_budget' => $criticalBudget,
            'points' => $points,
        ];
    }
}
