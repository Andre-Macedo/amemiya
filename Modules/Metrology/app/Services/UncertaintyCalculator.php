<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

class UncertaintyCalculator
{
    /**
     * Calcula a incerteza expandida com base nas leituras e no padrão.
     * Implementação simplificada do método GUM.
     *
     * @param array<float> $readings
     * @param float $resolution Resolução do instrumento calibrado
     * @param float $standardResult Valor verdadeiro do padrão (certificado)
     * @param float $standardUncertainty Incerteza do padrão (expandida)
     * @param float $standardK Fator k do padrão (geralmente 2.00)
     * @return array{
     *     bias: float,
     *     expanded_uncertainty: float,
     *     budget: array
     * }
     */
    public function calculate(
        array $readings,
        float $resolution,
        float $standardResult,
        float $standardUncertainty = 0.0,
        float $standardK = 2.00
    ): array {
        if (empty($readings)) {
            return [
                'bias' => 0.0,
                'expanded_uncertainty' => 0.0,
                'budget' => []
            ];
        }

        // 1. Média e Tendência (Erro)
        $avg = MetrologyMath::calculateAverage($readings);
        $bias = MetrologyMath::calculateBias($avg, $standardResult);

        // 2. Fontes de Incerteza
        // A: Repetibilidade (Desvio Padrão da Média)
        $uA = MetrologyMath::calculateTypeA($readings);
        
        // B1: Resolução (Distribuição Retangular)
        $uRes = MetrologyMath::calculateTypeB_Resolution($resolution);
        
        // B2: Incerteza do Padrão (Normal, divisor k)
        $uStd = MetrologyMath::calculateTypeB_Standard($standardUncertainty, $standardK);

        // 3. Combinação (Incerteza Combinada)
        $result = MetrologyMath::calculateFinalUncertainty($uA, $uRes, $uStd);
        
        return [
            'bias' => $bias,
            'expanded_uncertainty' => $result['expanded_uncertainty'],
            'budget' => $result['budget'] ?? []
        ];
    }
}
