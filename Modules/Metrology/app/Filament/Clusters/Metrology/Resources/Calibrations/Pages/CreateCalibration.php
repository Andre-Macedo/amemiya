<?php

declare(strict_types=1);

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
        // Domain Validation: Prevent calibrating items that require maintenance
        $type = $data['calibrated_item_type'] ?? null;
        $id = $data['calibrated_item_id'] ?? null;

        if ($type && $id && class_exists($type)) {
            $item = $type::find($id);
            if ($item) {
                try {
                    (new \Modules\Metrology\Services\CalibrationValidator())->canBeCalibrated($item);
                } catch (\Modules\Metrology\Exceptions\MetrologyException $e) {
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

        if (isset($data['checklist_template_id']) && !empty($data['checklist_items'])) {
            $this->checklistData = [
                'template_id' => $data['checklist_template_id'],
                'items' => $data['checklist_items'],
            ];
        }

        unset($data['checklist_template_id'], $data['checklist_items']);

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $model = new ($this->getModel());
        $model->fill($data);

        // Inject transient data for Listeners
        if (! empty($this->checklistData)) {
            $model->checklistInput = $this->checklistData;
        }
        
        // Handle Kit Items if present (extracted in mutateFormData, likely same logic as checklist)
        // Note: Previous code accessed $this->data directly in afterCreate.
        // mutateFormDataBeforeCreate didn't extract kit_items_results. I need to do that.
        $kitItems = $this->data['kit_items_results'] ?? [];
        if (! empty($kitItems)) {
            $model->kitItemsInput = $kitItems;
        }

        $model->save();

        return $model;
    }
}
