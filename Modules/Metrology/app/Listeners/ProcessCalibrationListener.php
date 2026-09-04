<?php

declare(strict_types=1);

namespace Modules\Metrology\Listeners;

use Filament\Notifications\Notification;
use Modules\Metrology\Actions\CreateChecklistAction;
use Modules\Metrology\Actions\ProcessCalibrationAction;
use Modules\Metrology\Actions\UpdateReferenceStandardKitAction;
use Modules\Metrology\DTOs\ChecklistCreationData;
use Modules\Metrology\DTOs\KitUpdateData;
use Modules\Metrology\Events\CalibrationSaved;

class ProcessCalibrationListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected ProcessCalibrationAction $processAction,
        protected CreateChecklistAction $createChecklistAction,
        protected UpdateReferenceStandardKitAction $updateKitAction
    ) {}

    /**
     * Handle the event.
     */
    public function handle(CalibrationSaved $event): void
    {
        $calibration = $event->calibration;

        // 1. Cria Checklist se houver input (via Filament/API)
        if (! empty($calibration->checklistInput)) {
            $dto = ChecklistCreationData::fromArray($calibration->checklistInput);
            $this->createChecklistAction->execute($calibration, $dto);
        }

        // 2. Atualiza Itens do Kit se houver input
        if (! empty($calibration->kitItemsInput)) {
            $dto = KitUpdateData::fromArray($calibration->kitItemsInput);
            $this->updateKitAction->execute($calibration, $dto);
        }

        // 3. Processa Lógica Central de Calibração (Status, Data de Vencimento, Procedure Snapshot)
        $this->processAction->execute($calibration);

        // 4. Envia Notificação se Reprovado
        // Verifica se a classe de notificação do Filament existe e se não estamos em testes para evitar erros de resolução.
        if ($calibration->result === 'rejected'
            && class_exists(Notification::class)
            && ! app()->runningUnitTests()
        ) {
            Notification::make()
                ->warning()
                ->title('Atenção: Instrumento Reprovado')
                ->body('O desvio encontrado foi superior à incerteza/critério permitido. O status foi definido como "Reprovado" automaticamente.')
                ->persistent()
                ->send();
        }
    }
}
