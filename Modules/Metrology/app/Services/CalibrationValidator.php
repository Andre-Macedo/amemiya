<?php

declare(strict_types=1);

namespace Modules\Metrology\Services;

use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Exceptions\MetrologyException;

class CalibrationValidator
{
    /**
     * Determina se o item pode ser calibrado.
     * Regra: Itens 'Scrapped' (Sucateados), 'Lost' (Perdidos) ou 'Rejected' (Reprovados no passado) não devem ser calibrados sem manutenção prévia.
     *
     * @throws MetrologyException
     */
    public function canBeCalibrated(CalibratableItem $item): bool
    {
        // Garante tipagem forte para verificação de status (Enum ou string legado)
        $status = $item->status;

        // Se o modelo ainda não estiver usando Casts (legado), trata string estritamente
        if (is_string($status)) {
            if (in_array(strtolower($status), ['rejected', 'lost', 'scrapped'])) {
                throw new MetrologyException("Status do item '{$status}' impede calibração sem manutenção.");
            }

            return true;
        }

        // Verificação via Enum (Padrão atual)
        if ($status instanceof ItemStatus) {
            if (in_array($status, [ItemStatus::Rejected, ItemStatus::Lost, ItemStatus::Scrapped])) {
                throw new MetrologyException("Status do item '{$status->getLabel()}' impede calibração sem manutenção.");
            }
        }

        return true;
    }
}
