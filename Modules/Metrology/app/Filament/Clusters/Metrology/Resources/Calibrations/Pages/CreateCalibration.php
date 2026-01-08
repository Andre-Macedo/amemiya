<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\CalibrationResource;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;

class CreateCalibration extends CreateRecord
{
    protected static string $resource = CalibrationResource::class;

    protected array $checklistData = [];

    public static function getCluster(): ?string
    {
        return MetrologyCluster::class;
    }

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
//        dd($data);
        if (isset($data['checklist_template_id']) && !empty($data['checklist_items'])) {
            $this->checklistData = [
                'template_id' => $data['checklist_template_id'],
                'items' => $data['checklist_items'],
            ];
        }

        unset($data['checklist_template_id'], $data['checklist_items']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // 1. Processar Checklist
        if (!empty($this->checklistData)) {
            (new \Modules\Metrology\Actions\CreateChecklistAction())->execute(
                $this->record, 
                $this->checklistData
            );
        }

        // 2. Processar Itens do Kit
        $kitItems = $this->data['kit_items_results'] ?? [];
        if (!empty($kitItems)) {
            (new \Modules\Metrology\Actions\UpdateReferenceStandardKitAction())->execute(
                $this->record, 
                $kitItems
            );
        }

        // 3. Notificação de Reprovação
        if ($this->record->result === 'rejected') {
            Notification::make()
                ->warning()
                ->title('Atenção: Instrumento Reprovado')
                ->body('O desvio encontrado foi superior à incerteza/critério permitido. O status foi definido como "Reprovado" automaticamente.')
                ->persistent()
                ->send();
        }
    }

}
