<?php

declare(strict_types=1);

namespace Modules\IoT\Services;

use InvalidArgumentException;

class BearingKinematicsService
{
    /**
     * Catálogo de rolamentos industriais com parâmetros geométricos padronizados.
     * d: diâmetro da esfera/rolete (mm)
     * D: diâmetro primitivo / pitch diameter (mm)
     * n: número de corpos rolantes
     * alpha: ângulo de contato (graus)
     *
     * @var array<string, array{name: string, manufacturer: string, n: int, d: float, D: float, alpha: float}>
     */
    protected const PRESETS = [
        'skf_6204' => [
            'name' => 'SKF 6204',
            'manufacturer' => 'SKF',
            'n' => 8,
            'd' => 7.94,
            'D' => 33.5,
            'alpha' => 0.0,
        ],
        'ucp_204' => [
            'name' => 'UCP 204 (Mancal Pedestal)',
            'manufacturer' => 'NSK / FAG / SKF',
            'n' => 8,
            'd' => 7.94,
            'D' => 34.0,
            'alpha' => 0.0,
        ],
        'skf_6205' => [
            'name' => 'SKF 6205',
            'manufacturer' => 'SKF',
            'n' => 9,
            'd' => 7.94,
            'D' => 39.0,
            'alpha' => 0.0,
        ],
        'skf_6304' => [
            'name' => 'SKF 6304',
            'manufacturer' => 'SKF',
            'n' => 7,
            'd' => 9.53,
            'D' => 36.0,
            'alpha' => 0.0,
        ],
        'skf_2206' => [
            'name' => 'SKF 2206 (Autocompensador)',
            'manufacturer' => 'SKF',
            'n' => 28,
            'd' => 6.35,
            'D' => 46.0,
            'alpha' => 11.5,
        ],
    ];

    /**
     * @return array<string, array{name: string, manufacturer: string, n: int, d: float, D: float, alpha: float}>
     */
    public function getPresets(): array
    {
        return self::PRESETS;
    }

    /**
     * Calcula as frequências características de defeito a partir de um modelo pré-cadastrado.
     *
     * @return array{
     *     bearing_name: string,
     *     rpm: float,
     *     shaft_frequency_hz: float,
     *     bpfo_hz: float,
     *     bpfi_hz: float,
     *     bsf_hz: float,
     *     ftf_hz: float,
     *     orders: array{bpfo: float, bpfi: float, bsf: float, ftf: float},
     *     harmonics: array<string, array<int, float>>
     * }
     */
    public function calculateForPreset(string $presetKey, float $rpm): array
    {
        $normalizedKey = strtolower(str_replace(['-', ' '], '_', $presetKey));

        if (! isset(self::PRESETS[$normalizedKey])) {
            throw new InvalidArgumentException("Modelo de rolamento '{$presetKey}' não encontrado no catálogo.");
        }

        $preset = self::PRESETS[$normalizedKey];

        $calc = $this->calculateFrequencies(
            rpm: $rpm,
            ballCount: $preset['n'],
            ballDiameter: $preset['d'],
            pitchDiameter: $preset['D'],
            contactAngleDegrees: $preset['alpha']
        );

        $calc['bearing_name'] = $preset['name'];

        return $calc;
    }

    /**
     * Calcula as frequências de falha analíticas da cinemática de rolamentos.
     *
     * @param  float  $rpm  Rotação por minuto do eixo (1X)
     * @param  int  $ballCount  Número de corpos rolantes (N)
     * @param  float  $ballDiameter  Diâmetro da esfera/rolete em mm (d)
     * @param  float  $pitchDiameter  Diâmetro primitivo em mm (D)
     * @param  float  $contactAngleDegrees  Ângulo de contato em graus (alpha)
     * @return array{
     *     bearing_name: string,
     *     rpm: float,
     *     shaft_frequency_hz: float,
     *     bpfo_hz: float,
     *     bpfi_hz: float,
     *     bsf_hz: float,
     *     ftf_hz: float,
     *     orders: array{bpfo: float, bpfi: float, bsf: float, ftf: float},
     *     harmonics: array<string, array<int, float>>
     * }
     */
    public function calculateFrequencies(
        float $rpm,
        int $ballCount,
        float $ballDiameter,
        float $pitchDiameter,
        float $contactAngleDegrees = 0.0
    ): array {
        if ($pitchDiameter <= 0.0 || $ballDiameter <= 0.0) {
            throw new InvalidArgumentException('Os diâmetros primitivo e do corpo rolante devem ser estritamente positivos.');
        }

        if ($ballCount <= 0) {
            throw new InvalidArgumentException('O número de corpos rolantes deve ser maior que zero.');
        }

        $safeRpm = max(0.0, $rpm);
        $f0 = $safeRpm / 60.0; // Frequência do eixo 1X em Hz

        $alphaRad = deg2rad($contactAngleDegrees);
        $cosAlpha = cos($alphaRad);
        $ratio = ($ballDiameter / $pitchDiameter) * $cosAlpha;

        // Fórmulas canônicas da cinemática de rolamentos (ISO / Mobius Institute):
        // BPFO: Ball Pass Frequency Outer Race
        $bpfo = ($ballCount * $f0 / 2.0) * (1.0 - $ratio);

        // BPFI: Ball Pass Frequency Inner Race
        $bpfi = ($ballCount * $f0 / 2.0) * (1.0 + $ratio);

        // BSF: Ball Spin Frequency
        $ratioSquared = $ratio * $ratio;
        $bsf = ($pitchDiameter * $f0 / (2.0 * $ballDiameter)) * (1.0 - $ratioSquared);

        // FTF: Fundamental Train Frequency (Gaiola)
        $ftf = ($f0 / 2.0) * (1.0 - $ratio);

        $orderBpfo = $f0 > 0.0 ? round($bpfo / $f0, 3) : 0.0;
        $orderBpfi = $f0 > 0.0 ? round($bpfi / $f0, 3) : 0.0;
        $orderBsf = $f0 > 0.0 ? round($bsf / $f0, 3) : 0.0;
        $orderFtf = $f0 > 0.0 ? round($ftf / $f0, 3) : 0.0;

        return [
            'bearing_name' => 'Custom Rolling Bearing',
            'rpm' => round($safeRpm, 1),
            'shaft_frequency_hz' => round($f0, 2),
            'bpfo_hz' => round($bpfo, 2),
            'bpfi_hz' => round($bpfi, 2),
            'bsf_hz' => round($bsf, 2),
            'ftf_hz' => round($ftf, 2),
            'orders' => [
                'bpfo' => $orderBpfo,
                'bpfi' => $orderBpfi,
                'bsf' => $orderBsf,
                'ftf' => $orderFtf,
            ],
            'harmonics' => [
                'bpfo' => [
                    round($bpfo, 2),
                    round($bpfo * 2.0, 2),
                    round($bpfo * 3.0, 2),
                ],
                'bpfi' => [
                    round($bpfi, 2),
                    round($bpfi * 2.0, 2),
                    round($bpfi * 3.0, 2),
                ],
                '1x_shaft' => [
                    round($f0, 2),
                    round($f0 * 2.0, 2),
                    round($f0 * 3.0, 2),
                    round($f0 * 4.0, 2),
                ],
            ],
        ];
    }

    /**
     * Compara uma lista de picos de frequência encontrados na FFT com as frequências do rolamento.
     *
     * @param  array<int, array{frequency_hz: float, amplitude: float}>  $detectedPeaks
     * @return array<int, array{
     *     frequency_hz: float,
     *     amplitude: float,
     *     fault_match: string,
     *     expected_hz: float,
     *     deviation_percent: float
     * }>
     */
    public function matchSpectralPeaks(
        array $detectedPeaks,
        float $rpm,
        string $presetKey,
        float $tolerancePercent = 2.5
    ): array {
        $bearingData = $this->calculateForPreset($presetKey, $rpm);

        $expectedTargets = [
            '1X_SHAFT' => $bearingData['shaft_frequency_hz'],
            '2X_SHAFT' => $bearingData['shaft_frequency_hz'] * 2.0,
            '3X_SHAFT' => $bearingData['shaft_frequency_hz'] * 3.0,
            'BPFO' => $bearingData['bpfo_hz'],
            '2X_BPFO' => $bearingData['bpfo_hz'] * 2.0,
            'BPFI' => $bearingData['bpfi_hz'],
            '2X_BPFI' => $bearingData['bpfi_hz'] * 2.0,
            'BSF' => $bearingData['bsf_hz'],
            'FTF' => $bearingData['ftf_hz'],
        ];

        $matches = [];

        foreach ($detectedPeaks as $peak) {
            $freq = (float) ($peak['frequency_hz'] ?? 0.0);
            $amp = (float) ($peak['amplitude'] ?? 0.0);

            if ($freq <= 0.0) {
                continue;
            }

            $bestMatch = null;
            $minDeviation = PHP_FLOAT_MAX;

            foreach ($expectedTargets as $label => $targetFreq) {
                if ($targetFreq <= 0.0) {
                    continue;
                }

                $diff = abs($freq - $targetFreq);
                $percentDiff = ($diff / $targetFreq) * 100.0;

                if ($percentDiff <= $tolerancePercent && $percentDiff < $minDeviation) {
                    $minDeviation = $percentDiff;
                    $bestMatch = [
                        'frequency_hz' => round($freq, 2),
                        'amplitude' => round($amp, 3),
                        'fault_match' => $label,
                        'expected_hz' => round($targetFreq, 2),
                        'deviation_percent' => round($percentDiff, 2),
                    ];
                }
            }

            if ($bestMatch !== null) {
                $matches[] = $bestMatch;
            }
        }

        return $matches;
    }
}
