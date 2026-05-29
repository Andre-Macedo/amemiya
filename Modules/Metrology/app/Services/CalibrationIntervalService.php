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
        // Requer pelo menos 3 calibrações para uma análise de tendência segura
        $calibrations = $instrument->calibrations()
            ->whereIn('result', ['approved', 'approved_with_restrictions']) // Ignora rejeitadas para a tendência de estabilidade, mas rejeições deveriam resetar o intervalo.
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
