<?php

declare(strict_types=1);

namespace Modules\Metrology\Listeners;

use Modules\Metrology\Actions\ProcessCalibrationAction;
use Modules\Metrology\Events\CalibrationSaved;

class ProcessCalibrationListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected ProcessCalibrationAction $processAction,
        protected \Modules\Metrology\Actions\CreateChecklistAction $createChecklistAction,
        protected \Modules\Metrology\Actions\UpdateReferenceStandardKitAction $updateKitAction
    ) {}

    /**
     * Handle the event.
     */
    public function handle(CalibrationSaved $event): void
    {
        $calibration = $event->calibration;

        // 1. Process Core Calibration Logic (Status, Due Date)
        $this->processAction->execute($calibration);

        // 2. Create Checklist if input provided (from Filament/API)
        if (! empty($calibration->checklistInput)) {
            $this->createChecklistAction->execute($calibration, $calibration->checklistInput);
        }

        // 3. Update Kit Items if input provided
        if (! empty($calibration->kitItemsInput)) {
            $this->updateKitAction->execute($calibration, $calibration->kitItemsInput);
        }

        // 4. Send Notification if Rejected (and running in a context that supports it?)
        // Ideally we should check if running in console or http, but Filament notifications are harmless if not rendered.
        if ($calibration->result === 'rejected' && class_exists(\Filament\Notifications\Notification::class)) {
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Atenção: Instrumento Reprovado')
                ->body('O desvio encontrado foi superior à incerteza/critério permitido. O status foi definido como "Reprovado" automaticamente.')
                ->persistent()
                ->send();
        }
    }
}
