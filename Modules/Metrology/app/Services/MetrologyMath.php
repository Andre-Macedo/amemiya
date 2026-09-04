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

    /**
     * Calcula os graus de liberdade efetivos via fórmula de Welch-Satterthwaite (GUM §G.4.1).
     *
     * @param  float  $uA  Incerteza padrão Tipo A
     * @param  int  $n  Número de leituras
     * @param  float  $uc  Incerteza combinada total
     * @return float Graus de liberdade efetivos (Veff)
     */
    public static function calculateVeff(float $uA, int $n, float $uc): float
    {
        if ($n < 2 || $uA <= 0.0 || $uc <= 0.0) {
            return INF;
        }

        $viA = $n - 1;
        $numerator = pow($uc, 4);
        $denominator = pow($uA, 4) / $viA;

        if ($denominator <= 0.0) {
            return INF;
        }

        return max(1.0, $numerator / $denominator);
    }

    /**
     * Retorna o fator de cobertura k para graus de liberdade efetivos (Veff).
     * Baseado na distribuição t de Student bilateral (95.45% de confiança - GUM Tabela G.2).
     *
     * @param  float  $veff  Graus de liberdade efetivos
     * @return float Fator k
     */
    public static function getKFromVeff(float $veff): float
    {
        if (is_infinite($veff) || $veff >= 100) {
            return 2.00;
        }

        $table = [
            1 => 13.97,
            2 => 4.303,
            3 => 3.182,
            4 => 2.776,
            5 => 2.571,
            6 => 2.447,
            7 => 2.365,
            8 => 2.306,
            9 => 2.262,
            10 => 2.228,
            11 => 2.201,
            12 => 2.179,
            13 => 2.160,
            14 => 2.145,
            15 => 2.131,
            16 => 2.120,
            17 => 2.110,
            18 => 2.101,
            19 => 2.093,
            20 => 2.086,
            25 => 2.060,
            30 => 2.042,
            35 => 2.030,
            40 => 2.021,
            50 => 2.009,
            60 => 2.000,
        ];

        $keys = array_keys($table);
        $closest = $keys[0];
        foreach ($keys as $key) {
            if (abs($key - $veff) < abs($closest - $veff)) {
                $closest = $key;
            }
        }

        return $table[$closest];
    }
}
