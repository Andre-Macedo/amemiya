<?php

namespace Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\Calibrations\CalibrationResource;
use Modules\Metrology\Models\ChecklistItem;

class EditCalibration extends EditRecord
{
    protected static string $resource = CalibrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['checklist_items']) && $this->record->checklist_id) {
            $checklist = $this->record->checklist;
            $checklist->items()->delete();
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
                ];
            }, $data['checklist_items']);
            ChecklistItem::insert($items);
            $checklist->update(['completed' => !in_array(false, array_column($data['checklist_items'], 'completed'))]);
        }
        unset($data['checklist_template_id'], $data['checklist_items']);
        return $data;
    }

}
