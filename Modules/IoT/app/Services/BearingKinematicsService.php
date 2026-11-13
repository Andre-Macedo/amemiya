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
        'skf_6203' => [
            'name' => 'SKF 6203',
            'manufacturer' => 'SKF',
            'n' => 8,
            'd' => 6.75,
            'D' => 28.5,
            'alpha' => 0.0,
        ],
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
        $aliases = [
            '6203' => 'skf_6203',
            '6204' => 'skf_6204',
            '6205' => 'skf_6205',
            '6304' => 'skf_6304',
            '2206' => 'skf_2206',
            'ucp204' => 'ucp_204',
        ];
        $normalizedKey = $aliases[$normalizedKey] ?? $normalizedKey;

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
                'bpfi_sidebands' => ($bpfi > $f0 && $f0 > 0.0)
                    ? [
                        round($bpfi - $f0, 2),
                        round($bpfi + $f0, 2),
                    ]
                    : [],
            ],
        ];
    }

    /**
     * Resolve frequências características usando preset ou geometria customizada cadastrada na máquina.
     *
     * @param  array<string, mixed>  $machineMeta
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
    public function calculateForMachine(array $machineMeta, float $rpm): array
    {
        $geometry = is_array($machineMeta['bearing_geometry'] ?? null)
            ? $machineMeta['bearing_geometry']
            : null;
        $bearingModel = (string) ($machineMeta['bearing_model'] ?? 'skf_6204');

        if ($geometry !== null && ($bearingModel === 'custom' || ! isset(self::PRESETS[strtolower(str_replace(['-', ' '], '_', $bearingModel))]))) {
            $ballCount = (int) ($geometry['n'] ?? 0);
            $ballDiameter = (float) ($geometry['d'] ?? 0.0);
            $pitchDiameter = (float) ($geometry['D'] ?? 0.0);
            $contactAngle = (float) ($geometry['alpha'] ?? 0.0);

            if ($ballCount > 0 && $ballDiameter > 0.0 && $pitchDiameter > 0.0) {
                $calc = $this->calculateFrequencies(
                    rpm: $rpm,
                    ballCount: $ballCount,
                    ballDiameter: $ballDiameter,
                    pitchDiameter: $pitchDiameter,
                    contactAngleDegrees: $contactAngle
                );
                $customName = trim((string) ($geometry['bearing_name'] ?? ''));
                $calc['bearing_name'] = $customName !== '' ? $customName : 'Rolamento Customizado';

                return $calc;
            }
        }

        return $this->calculateForPreset($bearingModel, $rpm);
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

        return $this->matchSpectralPeaksForKinematics($detectedPeaks, $bearingData, $tolerancePercent);
    }

    /**
     * Compara uma lista de picos espectrais contra um mapa cinemático pré-calculado (preset ou customizado).
     *
     * @param  array<int, array{frequency_hz?: float|int, freq_hz?: float|int, amplitude?: float|int, amp_g?: float|int}>  $detectedPeaks
     * @param  array<string, mixed>  $bearingData
     * @return array<int, array{
     *     frequency_hz: float,
     *     amplitude: float,
     *     fault_match: string,
     *     expected_hz: float,
     *     deviation_percent: float
     * }>
     */
    public function matchSpectralPeaksForKinematics(
        array $detectedPeaks,
        array $bearingData,
        float $tolerancePercent = 2.5
    ): array {
        $shaftHz = (float) ($bearingData['shaft_frequency_hz'] ?? 0.0);
        $bpfoHz = (float) ($bearingData['bpfo_hz'] ?? 0.0);
        $bpfiHz = (float) ($bearingData['bpfi_hz'] ?? 0.0);
        $bsfHz = (float) ($bearingData['bsf_hz'] ?? 0.0);
        $ftfHz = (float) ($bearingData['ftf_hz'] ?? 0.0);

        $expectedTargets = [
            '1X_SHAFT' => $shaftHz,
            '2X_SHAFT' => $shaftHz * 2.0,
            '3X_SHAFT' => $shaftHz * 3.0,
            'BPFO' => $bpfoHz,
            '2X_BPFO' => $bpfoHz * 2.0,
            'BPFI' => $bpfiHz,
            '2X_BPFI' => $bpfiHz * 2.0,
            'BSF' => $bsfHz,
            'FTF' => $ftfHz,
        ];

        if ($bpfiHz > $shaftHz && $shaftHz > 0.0) {
            $expectedTargets['BPFI_MINUS_1X'] = $bpfiHz - $shaftHz;
            $expectedTargets['BPFI_PLUS_1X'] = $bpfiHz + $shaftHz;
        }

        $matches = [];

        foreach ($detectedPeaks as $peak) {
            $freq = (float) ($peak['frequency_hz'] ?? $peak['freq_hz'] ?? 0.0);
            $amp = (float) ($peak['amplitude'] ?? $peak['amp_g'] ?? 0.0);

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

    /**
     * Extrai os picos espectrais dominantes de um espectro FFT unilateral com interpolação parabólica.
     *
     * @param  array<int, float|int>  $fftMagnitudes
     * @return array<int, array{frequency_hz: float, amplitude: float}>
     */
    public function extractPeaksFromFft(
        array $fftMagnitudes,
        float $sampleRate = 3200.0,
        float $minHz = 5.0,
        float $maxHz = 1200.0,
        int $maxPeaks = 12,
        float $minAmplitude = 0.001
    ): array {
        $magnitudes = array_values(array_map(static fn (mixed $value): float => (float) $value, $fftMagnitudes));
        $binCount = count($magnitudes);

        if ($binCount < 3 || $sampleRate <= 0.0) {
            return [];
        }

        $nyquistBins = ($binCount % 2 === 1)
            ? ($binCount - 1)
            : $binCount;

        $df = ($sampleRate / 2.0) / $nyquistBins;
        $peaks = [];

        for ($i = 1; $i < $binCount - 1; $i++) {
            $center = $magnitudes[$i];

            if ($center < $minAmplitude) {
                continue;
            }

            $left = $magnitudes[$i - 1];
            $right = $magnitudes[$i + 1];

            if ($center <= $left || $center < $right) {
                continue;
            }

            $denominator = $left - (2.0 * $center) + $right;
            $delta = abs($denominator) > 1e-12
                ? max(-0.5, min(0.5, 0.5 * ($left - $right) / $denominator))
                : 0.0;

            $frequencyHz = ($i + $delta) * $df;

            if ($frequencyHz < $minHz || $frequencyHz > $maxHz) {
                continue;
            }

            $peaks[] = [
                'frequency_hz' => round($frequencyHz, 2),
                'amplitude' => round($center, 4),
            ];
        }

        usort($peaks, static fn (array $a, array $b): int => $b['amplitude'] <=> $a['amplitude']);

        return array_slice($peaks, 0, $maxPeaks);
    }

    /**
     * Estima a rotação do eixo (RPM) a partir de picos de aceleração via pontuação harmônica (1X + 2X + 3X).
     *
     * @param  array<int, array{frequency_hz?: float|int, freq_hz?: float|int, amplitude?: float|int, amp_g?: float|int}>  $peaks
     * @return ?array{
     *     rpm: float,
     *     shaft_frequency_hz: float,
     *     confidence: float,
     *     harmonic_hits: int
     * }
     */
    public function estimateRpmFromPeaks(
        array $peaks,
        float $minRpm = 400.0,
        float $maxRpm = 3800.0,
        float $tolerancePercent = 3.5,
        float $minAmplitude = 0.001
    ): ?array {
        if (empty($peaks)) {
            return null;
        }

        $minHz = max(1.0, $minRpm / 60.0);
        $maxHz = max($minHz, $maxRpm / 60.0);

        $validPeaks = [];

        foreach ($peaks as $peak) {
            $freq = (float) ($peak['frequency_hz'] ?? $peak['freq_hz'] ?? 0.0);
            $amp = (float) ($peak['amplitude'] ?? $peak['amp_g'] ?? 0.0);

            if ($freq <= 0.0 || $amp < $minAmplitude) {
                continue;
            }

            $validPeaks[] = ['frequency_hz' => $freq, 'amplitude' => $amp];
        }

        if (empty($validPeaks)) {
            return null;
        }

        $candidates = [];

        foreach ($validPeaks as $peak) {
            $freq = $peak['frequency_hz'];

            if ($freq >= $minHz && $freq <= $maxHz) {
                $candidates[] = $freq;
            }
        }

        if (empty($candidates)) {
            return null;
        }

        $harmonicWeights = [1 => 1.0, 2 => 0.7, 3 => 0.4];
        $bestFreq = null;
        $bestScore = -1.0;
        $bestHits = 0;
        $totalScorePool = 0.0;

        foreach ($candidates as $candidateHz) {
            $score = 0.0;
            $hits = 0;

            foreach ($harmonicWeights as $order => $weight) {
                $targetHz = $candidateHz * $order;
                $bestMatchAmp = 0.0;

                foreach ($validPeaks as $peak) {
                    $percentDiff = (abs($peak['frequency_hz'] - $targetHz) / $targetHz) * 100.0;

                    if ($percentDiff <= $tolerancePercent && $peak['amplitude'] > $bestMatchAmp) {
                        $bestMatchAmp = $peak['amplitude'];
                    }
                }

                if ($bestMatchAmp > 0.0) {
                    $score += $bestMatchAmp * $weight;
                    $hits++;
                }
            }

            $totalScorePool += $score;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestFreq = $candidateHz;
                $bestHits = $hits;
            }
        }

        if ($bestFreq === null || $bestScore <= 0.0) {
            return null;
        }

        $dominance = $totalScorePool > 0.0
            ? $bestScore / $totalScorePool
            : 0.0;

        $confidence = min(0.99, round((0.55 * $dominance) + (0.15 * min(3, $bestHits)), 2));

        return [
            'rpm' => round($bestFreq * 60.0, 1),
            'shaft_frequency_hz' => round($bestFreq, 2),
            'confidence' => $confidence,
            'harmonic_hits' => $bestHits,
        ];
    }

    /**
     * Resolve o RPM efetivo priorizando medição direta, inferência pelo acelerômetro e fallback nominal.
     *
     * @param  array<int, array{frequency_hz?: float|int, freq_hz?: float|int, amplitude?: float|int, amp_g?: float|int}>  $peaks
     * @param  array<int, float|int>  $fftMagnitudes
     * @return array{
     *     rpm: float,
     *     rpm_source: string,
     *     confidence: ?float,
     *     extracted_peaks: array<int, array{frequency_hz: float, amplitude: float}>
     * }
     */
    public function resolveSpectralRpm(
        ?float $explicitRpm = null,
        ?float $firmwareEstimatedRpm = null,
        array $peaks = [],
        array $fftMagnitudes = [],
        float $sampleRate = 3200.0,
        ?float $nominalRpmFallback = null,
        float $minRpm = 400.0,
        float $maxRpm = 3800.0
    ): array {
        $extractedPeaks = empty($peaks) && ! empty($fftMagnitudes)
            ? $this->extractPeaksFromFft($fftMagnitudes, $sampleRate)
            : $peaks;

        if ($explicitRpm !== null && $explicitRpm > 100.0) {
            return [
                'rpm' => round($explicitRpm, 1),
                'rpm_source' => 'tachometer',
                'confidence' => 1.0,
                'extracted_peaks' => $extractedPeaks,
            ];
        }

        if ($firmwareEstimatedRpm !== null && $firmwareEstimatedRpm > 100.0) {
            return [
                'rpm' => round($firmwareEstimatedRpm, 1),
                'rpm_source' => 'accelerometer_firmware',
                'confidence' => null,
                'extracted_peaks' => $extractedPeaks,
            ];
        }

        $estimated = $this->estimateRpmFromPeaks($extractedPeaks, $minRpm, $maxRpm);

        if ($estimated !== null) {
            return [
                'rpm' => $estimated['rpm'],
                'rpm_source' => 'accelerometer_fft',
                'confidence' => $estimated['confidence'],
                'extracted_peaks' => $extractedPeaks,
            ];
        }

        if ($nominalRpmFallback !== null && $nominalRpmFallback > 100.0) {
            return [
                'rpm' => round($nominalRpmFallback, 1),
                'rpm_source' => 'nominal_fallback',
                'confidence' => null,
                'extracted_peaks' => $extractedPeaks,
            ];
        }

        return [
            'rpm' => 1750.0,
            'rpm_source' => 'default_fallback',
            'confidence' => null,
            'extracted_peaks' => $extractedPeaks,
        ];
    }
}
