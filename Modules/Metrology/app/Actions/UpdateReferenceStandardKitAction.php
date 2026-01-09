<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;

class UpdateReferenceStandardKitAction
{
    /**
     * Atualiza os valores reais dos itens filhos de um Kit (Padrão de Referência).
     *
     * @param Calibration $calibration Calibração pai (usada para data de vencimento)
     * @param array $kitItemsResults Dados do repeater ['child_id', 'new_actual_value']
     */
    public function execute(Calibration $calibration, array $kitItemsResults): void
    {
        foreach ($kitItemsResults as $itemData) {
            $child = ReferenceStandard::find($itemData['child_id']);
            
            if ($child) {
                $child->update([
                    'actual_value' => $itemData['new_actual_value'],
                    'calibration_due' => $calibration->calibration_date->copy()->addMonths(24),
                    'status' => \Modules\Metrology\Enums\ItemStatus::Active,
                ]);
            }
        }
    }
}
