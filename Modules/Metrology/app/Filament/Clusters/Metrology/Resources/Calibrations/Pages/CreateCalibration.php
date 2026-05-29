<?php

declare(strict_types=1);

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Metrology\Exceptions\MetrologyException;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\CalibrationResource;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Services\CalibrationValidator;

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
        // Validação de Domínio: Previne calibração de itens em manutenção
        $type = $data['calibrated_item_type'] ?? null;
        $id = $data['calibrated_item_id'] ?? null;

        if ($type && $id && class_exists($type)) {
            $item = $type::find($id);
            if ($item) {
                try {
                    (new CalibrationValidator)->canBeCalibrated($item);
                } catch (MetrologyException $e) {
                    Notification::make()
                        ->danger()
                        ->title('Operação Inválida')
                        ->body($e->getMessage())
                        ->persistent()
                        ->send();

                    $this->halt();
                }
            }
        }

        if (isset($data['checklist_template_id']) && ! empty($data['checklist_items'])) {
            $this->checklistData = [
                'template_id' => $data['checklist_template_id'],
                'items' => $data['checklist_items'],
            ];
        }

        unset($data['checklist_template_id'], $data['checklist_items']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $model = new ($this->getModel());
        $model->fill($data);

        // Injeta dados transientes para os Listeners
        if (! empty($this->checklistData)) {
            $model->checklistInput = $this->checklistData;
        }

        // Processa Itens do Kit se presentes (logica similar ao checklist)
        // Nota: O código anterior acessava $this->data diretamente no afterCreate.
        // mutateFormDataBeforeCreate não extraía kit_items_results. Preciso fazer isso aqui.
        $kitItems = $this->data['kit_items_results'] ?? [];
        if (! empty($kitItems)) {
            $model->kitItemsInput = $kitItems;
        }

        $model->save();

        return $model;
    }
}
