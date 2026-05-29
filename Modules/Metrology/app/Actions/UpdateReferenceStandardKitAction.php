<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\DTOs\KitUpdateData;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;

class UpdateReferenceStandardKitAction
{
    /**
     * Atualiza os valores reais dos itens filhos de um Kit (Padrão de Referência).
     *
     * Utilizado quando a calibração de um kit envolve a atualização dos valores medidos
     * de seus componentes individuais.
     *
     * @param  Calibration  $calibration  Calibração pai (usada para calcular a próxima data de vencimento).
     * @param  KitUpdateData  $kitData  Dados contendo os novos valores para os itens do kit.
     */
    public function execute(Calibration $calibration, KitUpdateData $kitData): void
    {
        foreach ($kitData->items as $itemData) {
            $child = ReferenceStandard::find($itemData->childId);

            if ($child) {
                $child->update([
                    'actual_value' => $itemData->newActualValue,
                    'calibration_due' => $calibration->calibration_date->copy()->addMonths(24),
                    'status' => ItemStatus::Active,
                ]);
            }
        }
    }
}
