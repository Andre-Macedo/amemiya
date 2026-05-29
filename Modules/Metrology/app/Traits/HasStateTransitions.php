<?php

namespace Modules\Metrology\Traits;

use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Exceptions\InvalidStateTransitionException;

trait HasStateTransitions
{
    /**
     * Mapa de transições permitidas.
     * [Estado Atual => [Estados de Destino Permitidos]]
     */
    protected function getAllowedTransitions(): array
    {
        return [
            ItemStatus::Active->value => [
                ItemStatus::Inactive,
                ItemStatus::Maintenance,
                ItemStatus::InCalibration,
                ItemStatus::Rejected,
                ItemStatus::Lost,
                ItemStatus::Expired,
                ItemStatus::Scrapped,
            ],
            ItemStatus::Inactive->value => [
                ItemStatus::Active,
                ItemStatus::InCalibration,
                ItemStatus::Maintenance,
                ItemStatus::Scrapped,
            ],
            ItemStatus::InCalibration->value => [
                ItemStatus::Active,   // Aprovado
                ItemStatus::Rejected, // Reprovado
                ItemStatus::Maintenance, // Precisou de ajuste durante calibração
            ],
            ItemStatus::Maintenance->value => [
                ItemStatus::InCalibration, // Pós-manutenção exige nova calibração
                ItemStatus::Inactive,
                ItemStatus::Scrapped,
            ],
            ItemStatus::Rejected->value => [
                ItemStatus::Maintenance,
                ItemStatus::InCalibration,
                ItemStatus::Scrapped,
            ],
            ItemStatus::Expired->value => [
                ItemStatus::InCalibration,
                ItemStatus::Inactive,
                ItemStatus::Scrapped,
            ],
            ItemStatus::Lost->value => [
                ItemStatus::Active,
                ItemStatus::Inactive,
                ItemStatus::Scrapped,
            ],
            ItemStatus::Scrapped->value => [], // Estado Terminal
        ];
    }

    /**
     * Valida a transição de estado.
     *
     * @throws InvalidStateTransitionException
     */
    public function transitionTo(ItemStatus $targetStatus): void
    {
        $currentStatus = $this->status;

        // Se o status for o mesmo, não faz nada
        if ($currentStatus === $targetStatus) {
            return;
        }

        $allowed = $this->getAllowedTransitions()[$currentStatus->value] ?? [];

        if (! in_array($targetStatus, $allowed)) {
            throw new InvalidStateTransitionException($currentStatus, $targetStatus);
        }

        $this->status = $targetStatus;
        $this->save();
    }
}
