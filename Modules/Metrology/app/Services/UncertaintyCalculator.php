<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

use Modules\Metrology\DTOs\MeasurementCalculationData;
use Modules\Metrology\DTOs\UncertaintyResult;

class UncertaintyCalculator
{
    protected ThermalCorrectionService $thermalService;

    public function __construct()
    {
        $this->thermalService = new ThermalCorrectionService;
    }

    /**
     * Calcula a incerteza expandida com base nas leituras e no padrão.
     * Implementação simplificada do método GUM com Correção Térmica.
     */
    public function calculate(
        MeasurementCalculationData|array $data,
        ?float $resolution = null,
        ?float $standardActualValue = null,
        ?float $standardUncertainty = null,
        float $standardK = 2.0
    ): UncertaintyResult {
        if (is_array($data)) {
            $data = new MeasurementCalculationData(
                readings: $data,
                resolution: (float) $resolution,
                standardActualValue: (float) $standardActualValue,
                standardUncertainty: (float) $standardUncertainty,
                standardK: $standardK
            );
        }

        if (empty($data->readings)) {
            return new UncertaintyResult(0.0, 0.0, [], 2.00);
        }

        $readings = $data->readings;
        $budgetExtras = [];

        // --- 1. Correção Térmica (Se aplicável) ---
        if ($data->temperature !== null) {
            // Corrige cada leitura individualmente para 20°C
            $correctedReadings = [];
            foreach ($readings as $r) {
                $correctedReadings[] = $this->thermalService->correctLength(
                    measuredLength: $r,
                    temperature: $data->temperature,
                    cte: $data->cte
                );
            }
            $readings = $correctedReadings; // Substitui para o cálculo da média

            // Calcula Incerteza da Correção Térmica
            // Assumindo u(T) = 0.5°C e u(CTE) = 10% do CTE (padrão conservador)
            // Usamos a média nominal para o cálculo de sensibilidade
            $nominalLength = MetrologyMath::calculateAverage($data->readings);

            $uThermal = $this->thermalService->calculateCorrectionUncertainty(
                length: $nominalLength,
                temperature: $data->temperature,
                uTemperature: 0.5, // Pode vir do DTO futuramente
                cte: $data->cte,
                uCte: $data->cte * 0.10
            );

            $budgetExtras[] = [
                'source' => 'Thermal Correction',
                'value' => $uThermal, // Já é incerteza padrão combinada
                'divisor' => 1,
                'distribution' => 'Normal', // Combinação de normais
                'standard_uncertainty' => $uThermal,
            ];
        }

        // --- 2. Média e Tendência (Erro Sistemático) ---
        $effectiveStandardValue = $data->standardActualValue;
        $hasMultipleStandards = ! empty($data->standards);

        if ($hasMultipleStandards && $effectiveStandardValue <= 0.0) {
            $sumActual = 0.0;
            foreach ($data->standards as $std) {
                $sumActual += (float) ($std['actual_value'] ?? 0.0);
            }
            if ($sumActual > 0.0) {
                $effectiveStandardValue = $sumActual;
            }
        }

        $avg = MetrologyMath::calculateAverage($readings);
        $bias = MetrologyMath::calculateBias($avg, $effectiveStandardValue);

        // --- 3. Fontes de Incerteza Básicas ---

        // A: Repetibilidade (Tipo A)
        $uA = MetrologyMath::calculateTypeA($readings);

        // B1: Resolução (Tipo B)
        $uRes = MetrologyMath::calculateTypeB_Resolution($data->resolution);

        // B2: Incerteza do Padrão (Tipo B) - Único ou Múltiplos Padrões combinados em quadratura
        $budgetStandards = [];
        $sumStdSquares = 0.0;

        if ($hasMultipleStandards) {
            foreach ($data->standards as $index => $std) {
                $stdU = (float) ($std['uncertainty'] ?? 0.0);
                $stdK = (float) ($std['k'] ?? 2.00);
                $stdName = (string) ($std['name'] ?? ('Reference Standard '.($index + 1)));

                $uStdSingle = MetrologyMath::calculateTypeB_Standard($stdU, $stdK);
                $sumStdSquares += pow($uStdSingle, 2);

                $budgetStandards[] = [
                    'source' => $stdName,
                    'value' => $stdU,
                    'divisor' => $stdK,
                    'distribution' => 'Normal',
                    'standard_uncertainty' => $uStdSingle,
                ];
            }
        } else {
            $uStd = MetrologyMath::calculateTypeB_Standard($data->standardUncertainty, $data->standardK);
            $sumStdSquares += pow($uStd, 2);

            $budgetStandards[] = [
                'source' => 'Reference Standard',
                'value' => $data->standardUncertainty,
                'divisor' => $data->standardK,
                'distribution' => 'Normal',
                'standard_uncertainty' => $uStd,
            ];
        }

        // --- 4. Combinação Final (GUM JCGM 100:2008) ---
        $sumSquares = pow($uA, 2) + pow($uRes, 2) + $sumStdSquares;

        foreach ($budgetExtras as $extra) {
            $sumSquares += pow($extra['standard_uncertainty'], 2);
        }

        $uc = sqrt($sumSquares);
        $veff = MetrologyMath::calculateVeff($uA, count($readings), $uc);
        $k = MetrologyMath::getKFromVeff($veff);
        $U = $uc * $k;

        // Monta o Budget Completo
        $budget = [
            [
                'source' => 'Repeatability (Type A)',
                'value' => $uA,
                'divisor' => 1,
                'distribution' => 'Normal',
                'standard_uncertainty' => $uA,
            ],
            [
                'source' => 'Resolution (Type B)',
                'value' => $data->resolution, // Valor cheio
                'divisor' => 3.464, // sqrt(12) ou 2*sqrt(3)
                'distribution' => 'Rectangular',
                'standard_uncertainty' => $uRes,
            ],
        ];

        // Adiciona Padrão(ões) de Referência
        $budget = array_merge($budget, $budgetStandards);

        // Adiciona os extras (Térmica)
        $budget = array_merge($budget, $budgetExtras);

        return new UncertaintyResult(
            bias: $bias,
            expandedUncertainty: round($U, 5),
            budget: $budget,
            kFactor: $k,
            effectiveDegreesOfFreedom: $veff,
        );
    }
}
