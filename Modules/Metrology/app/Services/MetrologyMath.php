<?php

namespace Modules\Metrology\Services;

class MetrologyMath
{
    /**
     * Calcula a Média Aritmética.
     */
    public static function calculateAverage(array $readings): float
    {
        if (empty($readings)) return 0.0;
        return array_sum($readings) / count($readings);
    }

    /**
     * Calcula o Erro (Tendência / Bias).
     * Fórmula: Média - Valor Verdadeiro do Padrão
     */
    public static function calculateBias(float $average, float $standardActualValue): float
    {
        return round($average - $standardActualValue, 5);
    }

    /**
     * Incerteza TIPO A: Repetibilidade.
     * Baseada na estatística das leituras.
     * Se usamos a média como resultado, usamos o "Desvio Padrão da Média" (Standard Error).
     * u_a = s / sqrt(n)
     */
    public static function calculateTypeA(array $readings): float
    {
        $n = count($readings);
        if ($n < 2) return 0.0;

        // 1. Calcula Desvio Padrão Amostral (s)
        $avg = self::calculateAverage($readings);
        $sumSquares = 0.0;
        foreach ($readings as $val) {
            $sumSquares += pow($val - $avg, 2);
        }
        $stdDev = sqrt($sumSquares / ($n - 1));

        // 2. Retorna a Incerteza da Média
        return $stdDev / sqrt($n);
    }

    /**
     * Incerteza TIPO B: Resolução do Instrumento.
     * Distribuição Retangular.
     * u_res = (Resolução / 2) / sqrt(3)  -> Para instrumentos digitais/analógicos comuns
     * Simplificado matematicamente: Resolução / 3.464
     */
    public static function calculateTypeB_Resolution(float $resolution): float
    {
        if ($resolution <= 0) return 0.0;

        // A semilargura é resolution / 2.
        // Divide-se pela raiz de 3 (distribuição retangular).
        return ($resolution / 2) / sqrt(3);
    }

    /**
     * Incerteza TIPO B: Do Padrão de Referência.
     * Distribuição Normal.
     * u_std = Incerteza Expandida do Certificado / k do Certificado
     */
    public static function calculateTypeB_Standard(float $stdUncertainty, float $stdK = 2.00): float
    {
        if ($stdK == 0) return $stdUncertainty; // Evita divisão por zero
        return $stdUncertainty / $stdK;
    }

    /**
     * CÁLCULO FINAL: Incerteza Expandida (U).
     * Raiz da soma dos quadrados (Root Sum Squares).
     */
    public static function calculateFinalUncertainty(
        float $u_typeA,       // Repetibilidade
        float $u_resolution,  // Resolução
        float $u_standard,    // Padrão
        float $k = 2.00       // Fator de Abrangência (Geralmente 2 para 95.45%)
    ): array
    {

        // 1. Incerteza Combinada (uc)
        // uc = sqrt( uA² + uRes² + uStd² )
        $sumSquares = pow($u_typeA, 2) + pow($u_resolution, 2) + pow($u_standard, 2);
        $uc = sqrt($sumSquares);

        // 2. Incerteza Expandida (U)
        // U = uc * k
        $U = $uc * $k;

        // 3. Graus de Liberdade Efetivos (Veff) - Opcional avançado (Welch-Satterthwaite)
        // Por enquanto, assumimos k=2 (infinitos graus de liberdade), que é padrão para industria geral.

        return [
            'combined_uncertainty' => round($uc, 6),
            'expanded_uncertainty' => round($U, 5), // Valor final para o certificado
            'k_factor' => $k,
            // Retornamos os componentes para ajudar a debugar se o valor ficar alto
            'components' => [
                'u_type_a' => $u_typeA,
                'u_resolution' => $u_resolution,
                'u_standard' => $u_standard
            ],
            // ADICIONAR ISSO: O "Extrato" bancário da incerteza
            'budget' => [
                [
                    'source' => 'Repetibilidade (Tipo A)',
                    'value' => $u_typeA,
                    'divisor' => 1,
                    'distribution' => 'Normal',
                    'standard_uncertainty' => $u_typeA // Já vem dividida
                ],
                [
                    'source' => 'Resolução do Instrumento',
                    'value' => $u_resolution * sqrt(3), // Valor cheio
                    'divisor' => 1.732,
                    'distribution' => 'Retangular',
                    'standard_uncertainty' => $u_resolution
                ],
                [
                    'source' => 'Certificado do Padrão',
                    'value' => $u_standard * 2, // Valor cheio (do certificado)
                    'divisor' => 2,
                    'distribution' => 'Normal',
                    'standard_uncertainty' => $u_standard
                ]
            ]
        ];
    }
}
