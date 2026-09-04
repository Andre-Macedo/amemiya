<?php

namespace Modules\Metrology\Services;

use Modules\Metrology\Models\Instrument;

class CalibrationIntervalService
{
    /**
     * Analisa o histórico do instrumento e sugere um novo intervalo.
     * Baseado no "Simple Response Method" (ILAC-G24 / OIML D 10).
     *
     * @return array|null Retorna null se não houver dados suficientes.
     */
    public function analyze(Instrument $instrument): ?array
    {
        // 1. Reset por reprovação recente (ILAC-G24 Seção 6.3)
        $latestRejection = $instrument->calibrations()
            ->where('result', 'rejected')
            ->latest('calibration_date')
            ->first();

        $latestApproval = $instrument->calibrations()
            ->whereIn('result', ['approved', 'approved_with_restrictions'])
            ->latest('calibration_date')
            ->first();

        if ($latestRejection && (
            ! $latestApproval ||
            $latestRejection->calibration_date->gt($latestApproval->calibration_date)
        )) {
            return [
                'type' => 'reset',
                'current_interval' => $instrument->getCalibrationFrequencyMonths(),
                'suggested_interval' => 3,
                'reliability_score' => 'Critical',
                'max_limit_usage' => 'N/A',
                'reason' => 'Recent rejection detected. Interval reset to minimum (3 months) per ILAC-G24 Section 6.3.',
                'method' => 'ILAC-G24 Simple Response',
            ];
        }

        // Requer pelo menos 3 calibrações para uma análise de tendência segura
        $calibrations = $instrument->calibrations()
            ->whereIn('result', ['approved', 'approved_with_restrictions'])
            ->latest('calibration_date')
            ->take(3)
            ->get();

        if ($calibrations->count() < 3) {
            return null;
        }

        $currentInterval = $instrument->getCalibrationFrequencyMonths();
        $mpe = $instrument->getMaximumPermissibleError();

        if ($mpe <= 0) {
            return [
                'type' => 'warning',
                'message' => 'Cannot calculate recommendation: Instrument MPE is not defined.',
            ];
        }

        // Analisa a performance de cada calibração em relação ao MPE
        $maxUsagePercent = 0;
        $worstCalibrationDate = null;

        foreach ($calibrations as $cal) {
            $error = abs((float) $cal->deviation);
            $uncertainty = abs((float) $cal->uncertainty);

            // Uso do Limite = (|Erro| + Incerteza) / MPE
            // Se for > 100%, o instrumento estaria reprovado tecnicamente (dependendo da regra de decisão).
            $usagePercent = (($error + $uncertainty) / $mpe) * 100;

            if ($usagePercent > $maxUsagePercent) {
                $maxUsagePercent = $usagePercent;
                $worstCalibrationDate = $cal->calibration_date->format('d/m/Y');
            }
        }

        // Lógica ILAC-G24 (Simplificada)

        // Cenário 1: Alta Confiabilidade (Erro < 50% do limite consistentemente)
        if ($maxUsagePercent <= 50) {
            $newInterval = min($currentInterval * 1.5, 24); // Aumenta 50%, teto de 24 meses
            $newInterval = floor($newInterval); // Arredonda para baixo (segurança)

            if ($newInterval > $currentInterval) {
                return [
                    'type' => 'increase',
                    'current_interval' => $currentInterval,
                    'suggested_interval' => (int) $newInterval,
                    'reliability_score' => 'High',
                    'max_limit_usage' => round($maxUsagePercent, 1).'%',
                    'reason' => 'Instrument consistently performs within 50% of its tolerance limits.',
                    'method' => 'ILAC-G24 Simple Response',
                ];
            }
        }

        // Cenário 2: Risco Moderado/Alto (Erro > 80% do limite)
        if ($maxUsagePercent >= 80) {
            $newInterval = max($currentInterval * 0.8, 3); // Reduz 20%, piso de 3 meses
            $newInterval = floor($newInterval);

            if ($newInterval < $currentInterval) {
                return [
                    'type' => 'decrease',
                    'current_interval' => $currentInterval,
                    'suggested_interval' => (int) $newInterval,
                    'reliability_score' => 'Low',
                    'max_limit_usage' => round($maxUsagePercent, 1).'%',
                    'reason' => "Instrument reached {$maxUsagePercent}% of tolerance on {$worstCalibrationDate}. Risk of Out-of-Tolerance.",
                    'method' => 'ILAC-G24 Simple Response',
                ];
            }
        }

        // Cenário 3: Manter (Entre 50% e 80%)
        return [
            'type' => 'maintain',
            'current_interval' => $currentInterval,
            'suggested_interval' => $currentInterval,
            'reliability_score' => 'Moderate',
            'max_limit_usage' => round($maxUsagePercent, 1).'%',
            'reason' => 'Instrument performance is stable but not sufficient to justify interval extension.',
            'method' => 'ILAC-G24 Simple Response',
        ];
    }
}
