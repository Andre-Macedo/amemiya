<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Metrology\Filament\Clusters\Metrology\MetrologyCluster;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\CalibrationResource;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;

class CreateCalibration extends CreateRecord
{
    protected static string $resource = CalibrationResource::class;

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
        if (isset($data['checklist_template_id']) && !empty($data['checklist_items'])) {
            $checklist = Checklist::create([
                'calibration_id' => null, // Will be set after calibration is created
                'checklist_template_id' => $data['checklist_template_id'],
                'completed' => false,
            ]);

            $items = array_map(function ($item) use ($checklist) {
                return [
                    'checklist_id' => $checklist->id,
                    'step' => $item['step'],
                    'question_type' => $item['question_type'],
                    'order' => $item['order'],
                    'required_readings' => $item['required_readings'],
                    'reference_standard_type' => $item['reference_standard_type'] ?? null,
                    'completed' => $item['completed'] ?? false,
                    'readings' => $item['readings'] ? array_column($item['readings'], 'value') : null,
                    'uncertainty' => $item['uncertainty'] ?? null,
                    'result' => $item['result'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'reference_standard_type_id' => $item['reference_standard_type_id'] ?? null,
                ];
            }, $data['checklist_items']);

            ChecklistItem::insert($items);
            $data['checklist_id'] = $checklist->id;
        }
        unset($data['checklist_template_id'], $data['checklist_items']);
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->checklist_id) {
            $checklist = Checklist::find($this->record->checklist_id);
            $checklist->update(['calibration_id' => $this->record->id]);
        }
    }

}
