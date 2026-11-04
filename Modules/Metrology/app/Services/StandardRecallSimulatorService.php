<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;

class StandardRecallSimulatorService
{
    /**
     * Simula o impacto retroativo de uma deriva metrológica observada em um padrão de referência.
     *
     * @return array{
     *     standard: array<string, mixed>,
     *     parameters: array<string, mixed>,
     *     summary: array{
     *         total_calibrations: int,
     *         unique_instruments: int,
     *         invalidated_count: int,
     *         critical_count: int,
     *         moderate_count: int,
     *         low_count: int,
     *         severity_level: string,
     *         recommendation: string
     *     },
     *     items: array<int, array<string, mixed>>
     * }
     */
    public function simulate(
        ReferenceStandard $standard,
        float $driftMagnitude,
        string $driftDirection = 'worst_case',
        ?string $startDate = null,
        ?string $endDate = null,
        float $additionalUncertainty = 0.0
    ): array {
        $query = Calibration::query()
            ->with(['calibratedItem.station', 'performedBy', 'checklist.items'])
            ->whereHas('checklist.items', function (Builder $builder) use ($standard): void {
                $builder->where('reference_standard_id', $standard->id);
            })
            ->where('status', '!=', 'draft');

        if ($startDate) {
            $query->whereDate('calibration_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('calibration_date', '<=', $endDate);
        }

        $calibrations = $query->orderBy('calibration_date', 'desc')->get();

        $items = [];
        $uniqueInstrumentIds = [];
        $invalidatedCount = 0;
        $criticalCount = 0;
        $moderateCount = 0;
        $lowCount = 0;

        foreach ($calibrations as $calibration) {
            $item = $calibration->calibratedItem;

            if (! $item instanceof Instrument) {
                continue;
            }

            $uniqueInstrumentIds[$item->id] = true;

            $mpe = $item->getMaximumPermissibleError();
            $originalDeviation = $calibration->deviation !== null ? (float) $calibration->deviation : 0.0;
            $originalUncertainty = $calibration->uncertainty !== null ? (float) $calibration->uncertainty : 0.0;

            $simulatedDeviation = $this->calculateSimulatedDeviation($originalDeviation, $driftMagnitude, $driftDirection);
            $simulatedUncertainty = $this->calculateSimulatedUncertainty($originalUncertainty, $additionalUncertainty);

            $strategy = $item->getDecisionRuleStrategy();

            $originalPass = $calibration->result === CalibrationResult::Approved
                || $calibration->result === CalibrationResult::ApprovedWithRestrictions
                || $calibration->result === 'pass'
                || $calibration->result === 'approved'
                || $calibration->result === 'approved_with_restrictions';

            $simulatedPass = true;
            if ($mpe !== null) {
                if ($mpe > 0) {
                    $simulatedPass = $strategy->evaluate(abs($simulatedDeviation), $simulatedUncertainty, $mpe);
                }
            }

            $tur = null;
            if ($mpe !== null) {
                if ($mpe > 0) {
                    if ($simulatedUncertainty > 0) {
                        $tur = round($mpe / $simulatedUncertainty, 2);
                    }
                }
            }

            $isInvalidated = $originalPass && ! $simulatedPass;

            if ($isInvalidated) {
                $invalidatedCount++;
                $risk = 'CRITICAL';
                $action = 'Recall Imediato / Quarentena & Bloqueio de Lotes';
                $criticalCount++;
            } elseif ($item->isCritical()) {
                $risk = 'CRITICAL';
                $action = 'Recalibração Preventiva Prioritária';
                $criticalCount++;
            } elseif ($tur !== null && $tur < 3.0) {
                $risk = 'MODERATE';
                $action = 'Recalibração Preventiva / Redução de Intervalo';
                $moderateCount++;
            } else {
                $risk = 'LOW';
                $action = 'Avaliação Documental / Manter em Operação';
                $lowCount++;
            }

            $items[] = [
                'calibration_id' => $calibration->id,
                'cert_code' => $calibration->certificate_code ?? $calibration->certificate_number ?? ('CERT-'.substr((string) $calibration->id, -6)),
                'date' => $calibration->calibration_date ? $calibration->calibration_date->format('d/m/Y') : '-',
                'instrument_id' => $item->id,
                'instrument_name' => $item->name,
                'tag' => $item->stock_number ?? '-',
                'serial' => $item->serial_number ?? '-',
                'location' => $item->station?->name ?? $item->location ?? '-',
                'criticality' => $item->criticality?->value ?? 'operational_reference',
                'is_critical_instrument' => $item->isCritical(),
                'mpe' => $mpe,
                'original_deviation' => $originalDeviation,
                'simulated_deviation' => $simulatedDeviation,
                'original_uncertainty' => $originalUncertainty,
                'simulated_uncertainty' => $simulatedUncertainty,
                'tur' => $tur,
                'original_pass' => $originalPass,
                'simulated_pass' => $simulatedPass,
                'is_invalidated' => $isInvalidated,
                'risk' => $risk,
                'action' => $action,
            ];
        }

        $severityLevel = 'LOW';
        if ($invalidatedCount > 0 || $criticalCount > 0) {
            $severityLevel = 'CRITICAL';
        } elseif ($moderateCount > 0) {
            $severityLevel = 'WARNING';
        }

        $recommendation = match ($severityLevel) {
            'CRITICAL' => "Risco Crítico Identificado: {$invalidatedCount} calibrações foram invalidadas pela deriva simulada. Recomenda-se formalização imediata de RNC, recolhimento dos instrumentos e auditoria das Ordens de Produção (OPs) associadas.",
            'WARNING' => "Alerta Metrológico: Nenhuma calibração foi formalmente invalidada, porém {$moderateCount} instrumentos apresentaram redução da Razão de Capacidade (TUR < 3). Recomenda-se antecipação da recalibração preventiva.",
            default => 'Impacto Controlado: Todos os instrumentos avaliados permanecem dentro dos Limites de Erro Máximo Permissível (MPE) com margem de segurança metrológica adequada.',
        };

        return [
            'standard' => [
                'id' => $standard->id,
                'name' => $standard->name,
                'stock_number' => $standard->stock_number,
                'serial_number' => $standard->serial_number,
                'nominal_value' => $standard->nominal_value,
                'uncertainty' => $standard->uncertainty,
                'unit' => $standard->unit,
                'last_calibration' => $standard->last_calibration_date?->format('d/m/Y'),
                'calibration_due' => $standard->calibration_due?->format('d/m/Y'),
            ],
            'parameters' => [
                'drift_magnitude' => $driftMagnitude,
                'drift_direction' => $driftDirection,
                'additional_uncertainty' => $additionalUncertainty,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_calibrations' => count($items),
                'unique_instruments' => count($uniqueInstrumentIds),
                'invalidated_count' => $invalidatedCount,
                'critical_count' => $criticalCount,
                'moderate_count' => $moderateCount,
                'low_count' => $lowCount,
                'severity_level' => $severityLevel,
                'recommendation' => $recommendation,
            ],
            'items' => $items,
        ];
    }

    private function calculateSimulatedDeviation(float $original, float $drift, string $direction): float
    {
        if ($direction === 'positive') {
            return round($original + $drift, 5);
        }

        if ($direction === 'negative') {
            return round($original - $drift, 5);
        }

        // worst_case: inflates the absolute error away from zero
        if ($original >= 0) {
            return round($original + $drift, 5);
        }

        return round($original - $drift, 5);
    }

    private function calculateSimulatedUncertainty(float $original, float $additional): float
    {
        if ($additional <= 0.0) {
            return $original;
        }

        // Quadrature combination of expanded uncertainties
        return round(sqrt(($original ** 2) + ((2.0 * $additional) ** 2)), 5);
    }
}
