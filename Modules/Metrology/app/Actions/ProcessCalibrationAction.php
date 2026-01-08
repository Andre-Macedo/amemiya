<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\DecisionRules\DecisionRuleStrategy;
use Modules\Metrology\Services\DecisionRules\GuardBand;
use Modules\Metrology\Services\DecisionRules\SimpleAcceptance;
use Modules\Metrology\Services\DecisionRules\UncertaintyAccounted;

class ProcessCalibrationAction
{
    public function execute(Calibration $calibration): void
    {
        $item = $calibration->calibratedItem;

        if (! $item) {
            return;
        }

        $this->evaluateResult($calibration, $item);
        
        // Salva as atualizações do resultado da calibração (o campo result pode ter mudado em evaluateResult)
        if ($calibration->isDirty('result')) {
            $calibration->saveQuietly();
        }

        $this->updateItemStatus($calibration, $item);
    }

    private function evaluateResult(Calibration $calibration, object $item): void
    {
        // Válido apenas para Instrumentos com EMP definido
        if ($item instanceof Instrument && $calibration->deviation !== null && $item->mpe) {
            $measuredError = abs((float) $calibration->deviation);
            $limit = $item->getMpeValue();

            if ($limit > 0) {
                $uncertainty = abs((float) $calibration->uncertainty);
                $rule = $item->getDecisionRule();
                
                $strategy = $this->getStrategy($rule);
                
                $passed = $strategy->evaluate($measuredError, $uncertainty, $limit);

                if (! $passed) {
                     $calibration->result = 'rejected';
                } else {
                    // Apenas atualiza para aprovado se não estiver marcado como "aprovado com restrições"
                    if ($calibration->result !== 'approved_with_restrictions') {
                        $calibration->result = 'approved';
                    }
                }
            }
        }
    }

    private function getStrategy(string $ruleName): DecisionRuleStrategy
    {
        return match ($ruleName) {
            'uncertainty_accounted' => new UncertaintyAccounted(),
            'guard_band' => new GuardBand(),
            default => new SimpleAcceptance(),
        };
    }

    private function updateItemStatus(Calibration $calibration, object $item): void
    {
        // CENÁRIO A: APROVADO
        if (in_array($calibration->result, ['approved', 'approved_with_restrictions'])) {
            $this->handleApproved($calibration, $item);
        } 
        // CENÁRIO B: REPROVADO
        elseif ($calibration->result === 'rejected') {
            $this->handleRejected($calibration, $item);
        }

        // Limpa o fornecedor atual para Instrumentos (pois retornou da calibração)
        if ($item instanceof Instrument) {
            $item->update(['current_supplier_id' => null]);
        }
    }

    private function handleApproved(Calibration $calibration, object $item): void
    {
        // 1. Calcular Data de Vencimento
        $months = 12; // Padrão
        
        if ($item instanceof Instrument) {
            $months = $item->instrumentType->calibration_frequency_months ?? 12;
        } elseif ($item instanceof ReferenceStandard) {
            $months = $item->referenceStandardType->calibration_frequency_months ?? 24;
        }
        
        $nextDate = $calibration->calibration_date->copy()->addMonths($months);

        // 2. Preparar Dados de Atualização
        $updateData = [
            'calibration_due' => $nextDate,
            'status' => 'active',
        ];

        // 3. Lógica Específica para Padrão de Referência (Atualizar Valor Real)
        if ($item instanceof ReferenceStandard) {
            if ($item->nominal_value && $calibration->deviation !== null) {
                $updateData['actual_value'] = (float) $item->nominal_value + (float) $calibration->deviation;
            }
            if ($calibration->uncertainty) {
                $updateData['uncertainty'] = $calibration->uncertainty;
            }
        }

        // 4. Salvar
        $item->update($updateData);

        // 5. Cascata para Filhos (para Kits)
        if ($item instanceof ReferenceStandard && $item->children()->exists()) {
            $item->children()->update([
                'calibration_due' => $nextDate,
                'status' => 'active',
            ]);
        }
    }

    private function handleRejected(Calibration $calibration, object $item): void
    {
        $item->update(['status' => 'rejected']);

        if ($item instanceof ReferenceStandard && $item->children()->exists()) {
            $item->children()->update(['status' => 'rejected']);
        }
    }
}
