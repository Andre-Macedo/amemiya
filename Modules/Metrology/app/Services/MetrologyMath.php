<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

class MetrologyMath
{
    /**
     * Calcula a Média Aritmética.
     */
    public static function calculateAverage(array $readings): float
    {
        if (empty($readings)) {
            return 0.0;
        }

        $sum = '0';
        foreach ($readings as $r) {
            $sum = PreciseMath::add($sum, $r);
        }

        return (float) PreciseMath::div($sum, count($readings));
    }

    /**
     * Calcula o Erro (Tendência / Bias).
     */
    public static function calculateBias(float $average, float $standardActualValue): float
    {
        return (float) PreciseMath::sub($average, $standardActualValue);
    }

    /**
     * Incerteza TIPO A: Repetibilidade.
     * u_a = s / sqrt(n)
     */
    public static function calculateTypeA(array $readings): float
    {
        $n = count($readings);
        if ($n < 2) {
            return 0.0;
        }

        $avg = self::calculateAverage($readings);
        $sumSquares = '0';

        foreach ($readings as $val) {
            $diff = PreciseMath::sub($val, $avg);
            $sumSquares = PreciseMath::add($sumSquares, PreciseMath::square($diff));
        }

        // Variância Amostral: s^2 = sumSquares / (n-1)
        $variance = PreciseMath::div($sumSquares, $n - 1);
        $stdDev = PreciseMath::sqrt($variance);

        // u_a = stdDev / sqrt(n)
        return (float) PreciseMath::div($stdDev, sqrt($n));
    }

    /**
     * Incerteza TIPO B: Resolução do Instrumento.
     * u_res = (Resolução / 2) / sqrt(3)
     */
    public static function calculateTypeB_Resolution(float $resolution): float
    {
        if ($resolution <= 0) {
            return 0.0;
        }

        $semiWidth = PreciseMath::div($resolution, 2);
        $sqrt3 = (string) sqrt(3);

        return (float) PreciseMath::div($semiWidth, $sqrt3);
    }

    /**
     * Incerteza TIPO B: Do Padrão de Referência.
     */
    public static function calculateTypeB_Standard(float $stdUncertainty, float $stdK = 2.00): float
    {
        if ($stdK == 0) {
            return $stdUncertainty;
        }

        return (float) PreciseMath::div($stdUncertainty, $stdK);
    }

    /**
     * CÁLCULO FINAL: Incerteza Expandida (U).
     */
    public static function calculateFinalUncertainty(
        float $u_typeA,
        float $u_resolution,
        float $u_standard,
        float $k = 2.00
    ): array {

        // Combined: uc = sqrt( uA² + uRes² + uStd² )
        $sumSquares = PreciseMath::add(
            PreciseMath::add(PreciseMath::square($u_typeA), PreciseMath::square($u_resolution)),
            PreciseMath::square($u_standard)
        );

        $uc = PreciseMath::sqrt($sumSquares);
        $U = PreciseMath::mul($uc, $k);

        return [
            'combined_uncertainty' => (float) round((float) $uc, 6),
            'expanded_uncertainty' => (float) round((float) $U, 5),
            'k_factor' => $k,
            'components' => [
                'u_type_a' => $u_typeA,
                'u_resolution' => $u_resolution,
                'u_standard' => $u_standard,
            ],
            'budget' => [
                [
                    'source' => 'Repetibilidade (Tipo A)',
                    'value' => $u_typeA,
                    'divisor' => 1,
                    'distribution' => 'Normal',
                    'standard_uncertainty' => $u_typeA,
                ],
                [
                    'source' => 'Resolução do Instrumento',
                    'value' => (float) PreciseMath::mul($u_resolution, sqrt(12)),
                    'divisor' => 3.464,
                    'distribution' => 'Retangular',
                    'standard_uncertainty' => $u_resolution,
                ],
                [
                    'source' => 'Certificado do Padrão',
                    'value' => (float) PreciseMath::mul($u_standard, $k),
                    'divisor' => $k,
                    'distribution' => 'Normal',
                    'standard_uncertainty' => $u_standard,
                ],
            ],
        ];
    }
}
