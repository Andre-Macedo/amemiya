<?php

declare(strict_types=1);

namespace Modules\IoT\Services;

class ISO20816SeverityService
{
    /**
     * Limiares de velocidade RMS (mm/s) conforme ISO 20816-3.
     */
    protected const THRESHOLDS = [
        // Máquinas pequenas (< 15 kW, ex: motor 1/4 CV da bancada TCC - ref. ISO 10816-1 Classe I)
        'small_machine' => [
            'rigid' => [
                'zone_a_max' => 0.71,
                'zone_b_max' => 1.8,
                'zone_c_max' => 4.5,
            ],
            'flexible' => [
                'zone_a_max' => 1.12,
                'zone_b_max' => 2.8,
                'zone_c_max' => 7.1,
            ],
        ],
        // Grupo 2: Motores de 15 kW a 300 kW (Padrão mais comum)
        'group_2' => [
            'rigid' => [
                'zone_a_max' => 1.4,
                'zone_b_max' => 2.8,
                'zone_c_max' => 4.5,
            ],
            'flexible' => [
                'zone_a_max' => 2.3,
                'zone_b_max' => 4.5,
                'zone_c_max' => 7.1,
            ],
        ],
        // Grupo 1: Grandes Máquinas (300 kW a 50 MW)
        'group_1' => [
            'rigid' => [
                'zone_a_max' => 2.3,
                'zone_b_max' => 4.5,
                'zone_c_max' => 7.1,
            ],
            'flexible' => [
                'zone_a_max' => 3.5,
                'zone_b_max' => 7.1,
                'zone_c_max' => 11.0,
            ],
        ],
    ];

    /**
     * Calcula a velocidade de vibração RMS (mm/s).
     *
     * @param  float|null  $rmsInG  Aceleração RMS em 'g' (Gravidade)
     * @param  int|null  $rpm  Rotação por minuto do eixo (1X)
     * @param  float|null  $explicitVelocityRms  Se o nó já enviou o v_rms calculado em mm/s
     */
    public function calculateVelocityRms(?float $rmsInG, ?int $rpm, ?float $explicitVelocityRms = null): ?float
    {
        // Se o firmware já forneceu a velocidade calculada
        if ($explicitVelocityRms !== null && $explicitVelocityRms > 0) {
            return round($explicitVelocityRms, 3);
        }

        if ($rmsInG === null || $rmsInG <= 0) {
            return null;
        }

        // Se RPM não foi fornecido, adota velocidade síncrona nominal típica de 4 polos (1.750 RPM)
        $effectiveRpm = ($rpm !== null && $rpm > 100) ? $rpm : 1750;

        // Conversão física analítica de Aceleração para Velocidade (v = a / (2 * pi * f))
        // 1 g = 9806.65 mm/s^2; f = RPM / 60 Hz
        // v_rms (mm/s) = (rms_g * 9806.65 * 60) / (2 * M_PI * RPM)
        $velocity = ($rmsInG * 9806.65 * 60.0) / (2.0 * M_PI * $effectiveRpm);

        return round($velocity, 3);
    }

    /**
     * Avalia e classifica o estado mecânico conforme a norma ISO 20816-3 / ISO 10816-1.
     *
     * @param  array<string, float|int>|null  $customThresholds
     * @return array<string, mixed>
     */
    public function evaluate(
        ?float $velocityRms,
        string $machineGroup = 'group_2',
        string $foundation = 'rigid',
        ?array $customThresholds = null
    ): array {
        if ($velocityRms === null || $velocityRms <= 0) {
            return [
                'zone' => null,
                'velocity_rms' => null,
                'iso_applicable' => $machineGroup !== 'baseline_only',
                'machine_group' => $machineGroup,
                'foundation' => $foundation,
                'status' => 'unknown',
                'label' => 'Não Determinado',
                'color' => '#6b7280',
                'description' => 'Dados de vibração insuficientes para cálculo normativo ISO 20816.',
                'action_required' => false,
            ];
        }

        if ($machineGroup === 'baseline_only') {
            return [
                'zone' => null,
                'velocity_rms' => $velocityRms,
                'iso_applicable' => false,
                'machine_group' => 'baseline_only',
                'foundation' => $foundation,
                'status' => 'not_applicable',
                'label' => 'Sem Enquadramento ISO (< 15 kW)',
                'color' => '#6b7280',
                'badge' => 'secondary',
                'description' => 'Ativo fora do escopo de potência da ISO 20816-3 (>= 15 kW). A severidade é acompanhada por velocidade RMS (mm/s), linha de base e IA.',
                'action_required' => false,
                'standard' => 'Monitoramento mm/s + Baseline/IA (Fora de escopo ISO 20816-3)',
            ];
        }

        $resolvedGroup = array_key_exists($machineGroup, self::THRESHOLDS) ? $machineGroup : 'group_2';
        $baseLimits = self::THRESHOLDS[$resolvedGroup][$foundation] ?? self::THRESHOLDS['group_2']['rigid'];
        $limits = $this->mergeCustomThresholds($baseLimits, $customThresholds);
        $standardLabel = $this->formatStandardLabel($resolvedGroup, $foundation, $customThresholds);

        if ($velocityRms <= $limits['zone_a_max']) {
            return [
                'zone' => 'A',
                'velocity_rms' => $velocityRms,
                'iso_applicable' => true,
                'machine_group' => $resolvedGroup,
                'foundation' => $foundation,
                'thresholds' => $limits,
                'status' => 'excellent',
                'label' => 'Zona A - Excelente',
                'color' => '#10b981', // emerald-500
                'badge' => 'success',
                'description' => 'Vibração típica de máquina nova ou recém-comissionada. Condição perfeita.',
                'action_required' => false,
                'standard' => $standardLabel,
            ];
        }

        if ($velocityRms <= $limits['zone_b_max']) {
            return [
                'zone' => 'B',
                'velocity_rms' => $velocityRms,
                'iso_applicable' => true,
                'machine_group' => $resolvedGroup,
                'foundation' => $foundation,
                'thresholds' => $limits,
                'status' => 'acceptable',
                'label' => 'Zona B - Aceitável',
                'color' => '#3b82f6', // blue-500
                'badge' => 'info',
                'description' => 'Operação normal e irrestrita a longo prazo. Sem necessidade de intervenção.',
                'action_required' => false,
                'standard' => $standardLabel,
            ];
        }

        if ($velocityRms <= $limits['zone_c_max']) {
            return [
                'zone' => 'C',
                'velocity_rms' => $velocityRms,
                'iso_applicable' => true,
                'machine_group' => $resolvedGroup,
                'foundation' => $foundation,
                'thresholds' => $limits,
                'status' => 'warning',
                'label' => 'Zona C - Alerta de Manutenção',
                'color' => '#f59e0b', // amber-500
                'badge' => 'warning',
                'description' => 'Operação restrita. A vibração é insatisfatória para operação contínua. Planejar intervenção mecânica.',
                'action_required' => true,
                'standard' => $standardLabel,
            ];
        }

        return [
            'zone' => 'D',
            'velocity_rms' => $velocityRms,
            'iso_applicable' => true,
            'machine_group' => $resolvedGroup,
            'foundation' => $foundation,
            'thresholds' => $limits,
            'status' => 'critical',
            'label' => 'Zona D - Perigo / Parada Imediata',
            'color' => '#ef4444', // red-500
            'badge' => 'destructive',
            'description' => 'Vibração excessiva e inaceitável. Risco iminente de quebra de eixo, mancal ou rotor. Parada de emergência recomendada.',
            'action_required' => true,
            'standard' => $standardLabel,
        ];
    }

    /**
     * @param  array{zone_a_max: float, zone_b_max: float, zone_c_max: float}  $baseLimits
     * @param  array<string, float|int>|null  $customThresholds
     * @return array{zone_a_max: float, zone_b_max: float, zone_c_max: float}
     */
    protected function mergeCustomThresholds(array $baseLimits, ?array $customThresholds): array
    {
        if ($customThresholds === null) {
            return $baseLimits;
        }

        $zoneAMax = isset($customThresholds['zone_a_max']) ? (float) $customThresholds['zone_a_max'] : $baseLimits['zone_a_max'];
        $zoneBMax = isset($customThresholds['zone_b_max']) ? (float) $customThresholds['zone_b_max'] : $baseLimits['zone_b_max'];
        $zoneCMax = isset($customThresholds['zone_c_max']) ? (float) $customThresholds['zone_c_max'] : $baseLimits['zone_c_max'];

        if ($zoneAMax <= 0 || $zoneBMax <= $zoneAMax || $zoneCMax <= $zoneBMax) {
            return $baseLimits;
        }

        return [
            'zone_a_max' => $zoneAMax,
            'zone_b_max' => $zoneBMax,
            'zone_c_max' => $zoneCMax,
        ];
    }

    /**
     * @param  array<string, float|int>|null  $customThresholds
     */
    protected function formatStandardLabel(string $machineGroup, string $foundation, ?array $customThresholds): string
    {
        $foundationLabel = $foundation === 'flexible' ? 'Base Flexível' : 'Base Rígida';

        $groupLabel = match ($machineGroup) {
            'small_machine' => 'ISO 10816-1 Classe I (< 15 kW',
            'group_1' => 'ISO 20816-3 (Grupo 1',
            default => 'ISO 20816-3 (Grupo 2',
        };

        $customSuffix = $customThresholds !== null ? ', Limiar Customizado' : '';

        return "{$groupLabel}, {$foundationLabel}{$customSuffix})";
    }
}
