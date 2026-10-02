<?php

declare(strict_types=1);

namespace Modules\IoT\Services;

class ISO20816SeverityService
{
    /**
     * Limiares de velocidade RMS (mm/s) conforme ISO 20816-3.
     */
    protected const THRESHOLDS = [
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
     * Avalia e classifica o estado mecânico conforme a norma ISO 20816-3.
     *
     * @return array<string, mixed>
     */
    public function evaluate(
        ?float $velocityRms,
        string $machineGroup = 'group_2',
        string $foundation = 'rigid'
    ): array {
        if ($velocityRms === null || $velocityRms <= 0) {
            return [
                'zone' => null,
                'velocity_rms' => null,
                'status' => 'unknown',
                'label' => 'Não Determinado',
                'color' => '#6b7280',
                'description' => 'Dados de vibração insuficientes para cálculo normativo ISO 20816.',
                'action_required' => false,
            ];
        }

        $limits = self::THRESHOLDS[$machineGroup][$foundation] ?? self::THRESHOLDS['group_2']['rigid'];

        if ($velocityRms <= $limits['zone_a_max']) {
            return [
                'zone' => 'A',
                'velocity_rms' => $velocityRms,
                'status' => 'excellent',
                'label' => 'Zona A - Excelente',
                'color' => '#10b981', // emerald-500
                'badge' => 'success',
                'description' => 'Vibração típica de máquina nova ou recém-comissionada. Condição perfeita.',
                'action_required' => false,
                'standard' => 'ISO 20816-3 (Grupo 2, Base Rígida)',
            ];
        }

        if ($velocityRms <= $limits['zone_b_max']) {
            return [
                'zone' => 'B',
                'velocity_rms' => $velocityRms,
                'status' => 'acceptable',
                'label' => 'Zona B - Aceitável',
                'color' => '#3b82f6', // blue-500
                'badge' => 'info',
                'description' => 'Operação normal e irrestrita a longo prazo. Sem necessidade de intervenção.',
                'action_required' => false,
                'standard' => 'ISO 20816-3 (Grupo 2, Base Rígida)',
            ];
        }

        if ($velocityRms <= $limits['zone_c_max']) {
            return [
                'zone' => 'C',
                'velocity_rms' => $velocityRms,
                'status' => 'warning',
                'label' => 'Zona C - Alerta de Manutenção',
                'color' => '#f59e0b', // amber-500
                'badge' => 'warning',
                'description' => 'Operação restrita. A vibração é insatisfatória para operação contínua. Planejar intervenção mecânica.',
                'action_required' => true,
                'standard' => 'ISO 20816-3 (Grupo 2, Base Rígida)',
            ];
        }

        return [
            'zone' => 'D',
            'velocity_rms' => $velocityRms,
            'status' => 'critical',
            'label' => 'Zona D - Perigo / Parada Imediata',
            'color' => '#ef4444', // red-500
            'badge' => 'destructive',
            'description' => 'Vibração excessiva e inaceitável. Risco iminente de quebra de eixo, mancal ou rotor. Parada de emergência recomendada.',
            'action_required' => true,
            'standard' => 'ISO 20816-3 (Grupo 2, Base Rígida)',
        ];
    }
}
