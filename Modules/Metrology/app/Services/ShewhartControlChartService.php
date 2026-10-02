<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

use Modules\Metrology\Models\Instrument;

class ShewhartControlChartService
{
    /**
     * Gera a análise estatística da Carta de Controle de Shewhart (ILAC-G24 / OIML D10 Método 2)
     * para as checagens intermediárias do instrumento.
     *
     * @return array{
     *     instrument_id: string,
     *     instrument_name: string,
     *     mpe: ?float,
     *     total_checks: int,
     *     has_sufficient_data: bool,
     *     statistics: array{
     *         mean: float,
     *         std_dev: float,
     *         ucl: float,
     *         lcl: float,
     *         uwl: float,
     *         lwl: float,
     *         usl: ?float,
     *         lsl: ?float
     *     }|null,
     *     in_control: bool,
     *     alerts: list<string>,
     *     points: list<array<string, mixed>>
     * }
     */
    public function analyze(Instrument $instrument): array
    {
        $checks = $instrument->intermediateChecks()
            ->with(['referenceStandard', 'performer'])
            ->orderBy('check_date', 'asc')
            ->get();

        $mpe = $instrument->getMaximumPermissibleError();
        $totalChecks = $checks->count();

        // Extrai os valores contínuos para a carta (desvio ou leitura)
        $values = [];
        $points = [];

        foreach ($checks as $check) {
            $deviation = $check->deviation !== null
                ? (float) $check->deviation
                : ($check->measured_value !== null && $check->nominal_value !== null
                    ? (float) $check->measured_value - (float) $check->nominal_value
                    : null);

            if ($deviation !== null) {
                $values[] = $deviation;
            }

            $points[] = [
                'id' => $check->id,
                'check_date' => $check->check_date?->format('Y-m-d'),
                'formatted_date' => $check->check_date?->format('d/m/Y'),
                'nominal_value' => $check->nominal_value !== null ? (float) $check->nominal_value : null,
                'measured_value' => $check->measured_value !== null ? (float) $check->measured_value : null,
                'deviation' => $deviation,
                'result' => $check->result,
                'temperature' => $check->temperature !== null ? (float) $check->temperature : null,
                'humidity' => $check->humidity !== null ? (float) $check->humidity : null,
                'standard_name' => $check->referenceStandard?->name,
                'performer_name' => $check->performer?->name,
                'notes' => $check->notes,
                'is_out_of_control' => false,
                'out_of_control_reason' => null,
            ];
        }

        $n = count($values);
        if ($n < 2) {
            return [
                'instrument_id' => $instrument->id,
                'instrument_name' => $instrument->name,
                'mpe' => $mpe > 0 ? (float) $mpe : null,
                'total_checks' => $totalChecks,
                'has_sufficient_data' => false,
                'statistics' => null,
                'in_control' => true,
                'alerts' => ['Mínimo de 2 medições com desvio registradas para o cálculo de limites de controle estatístico (3σ).'],
                'points' => $points,
            ];
        }

        // Estatística amostral
        $mean = array_sum($values) / $n;
        $variance = 0.0;
        foreach ($values as $val) {
            $variance += pow($val - $mean, 2);
        }
        $stdDev = sqrt($variance / ($n - 1));

        // Limites de Controle Shewhart (3-Sigma) e Advertência (2-Sigma)
        $ucl = round($mean + 3 * $stdDev, 5);
        $lcl = round($mean - 3 * $stdDev, 5);
        $uwl = round($mean + 2 * $stdDev, 5);
        $lwl = round($mean - 2 * $stdDev, 5);

        // Limites de Especificação (MPE)
        $usl = $mpe > 0 ? (float) $mpe : null;
        $lsl = $mpe > 0 ? -(float) $mpe : null;

        $alerts = [];
        $inControl = true;

        // Regras de Western Electric / Nelson Rules
        // Regra 1: Ponto fora de 3σ ou fora de MPE
        foreach ($points as &$pt) {
            if ($pt['deviation'] === null) {
                continue;
            }

            $dev = $pt['deviation'];

            if ($dev > $ucl || $dev < $lcl) {
                $pt['is_out_of_control'] = true;
                $pt['out_of_control_reason'] = 'Ponto além do Limite Superior/Inferior de Controle Estatístico (3σ)';
                $inControl = false;
                $alerts[] = "Ponto de {$pt['formatted_date']} violou os limites de controle estatístico 3σ (UCL/LCL).";
            } elseif ($usl !== null && ($dev > $usl || $dev < $lsl)) {
                $pt['is_out_of_control'] = true;
                $pt['out_of_control_reason'] = 'Ponto violou a tolerância máxima admissível (MPE)';
                $inControl = false;
                $alerts[] = "Ponto de {$pt['formatted_date']} ultrapassou o Erro Máximo Permissível (MPE).";
            }
        }
        unset($pt);

        // Regra 2: Tendência de 7 pontos consecutivos no mesmo lado da média
        if ($n >= 7) {
            $sideCount = 0;
            $currentSide = 0; // +1 acima, -1 abaixo
            foreach ($values as $val) {
                $side = ($val >= $mean) ? 1 : -1;
                if ($side === $currentSide) {
                    $sideCount++;
                    if ($sideCount >= 7) {
                        $inControl = false;
                        $alerts[] = 'Alerta de Deriva Sistemática: 7 ou mais pontos consecutivos no mesmo lado da média (Nelson Rule 2).';
                        break;
                    }
                } else {
                    $currentSide = $side;
                    $sideCount = 1;
                }
            }
        }

        return [
            'instrument_id' => $instrument->id,
            'instrument_name' => $instrument->name,
            'mpe' => $mpe > 0 ? (float) $mpe : null,
            'total_checks' => $totalChecks,
            'has_sufficient_data' => true,
            'statistics' => [
                'mean' => round($mean, 5),
                'std_dev' => round($stdDev, 5),
                'ucl' => $ucl,
                'lcl' => $lcl,
                'uwl' => $uwl,
                'lwl' => $lwl,
                'usl' => $usl,
                'lsl' => $lsl,
            ],
            'in_control' => $inControl,
            'alerts' => array_values(array_unique($alerts)),
            'points' => $points,
        ];
    }
}
