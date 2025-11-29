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
        if (!empty($this->checklistData)) {

            $checklist = Checklist::create([
                'calibration_id' => $this->record->id, // <--- AQUI ESTÁ A SOLUÇÃO
                'checklist_template_id' => $this->checklistData['template_id'],
                'completed' => false,
            ]);

            $items = array_map(function ($item) use ($checklist) {

                $hasResult = !empty($item['result']);
                $hasReadings = !empty($item['readings']) && isset($item['readings'][0]['value']);

                $isCompleted = $hasResult || $hasReadings;

                return [
                    'checklist_id' => $checklist->id,
                    'step' => $item['step'],
                    'question_type' => $item['question_type'],
                    'order' => $item['order'],
                    'required_readings' => $item['required_readings'] ?? 0,
//                    'reference_standard_type_id' => $item['reference_standard_type_id'] ?? null,
                    'completed' => $isCompleted,
                    'readings' => isset($item['readings']) ? json_encode(array_column($item['readings'], 'value')) : null,
                    'uncertainty' => $item['uncertainty'] ?? null,
                    'result' => $item['result'] ?? null, // Aqui vem o 'approved'/'rejected' do ToggleButtons
                    'notes' => $item['notes'] ?? null,
                    'reference_standard_id' => $item['reference_standard_id'] ?? null,
                ];
            }, $this->checklistData['items']);

            ChecklistItem::insert($items);

            $this->record->update(['checklist_id' => $checklist->id]);
        }

        $kitItems = $this->data['kit_items_results'] ?? [];

        if (!empty($kitItems)) {
            foreach ($kitItems as $itemData) {
                $child = \Modules\Metrology\Models\ReferenceStandard::find($itemData['child_id']);
                if ($child) {
                    $child->update([
                        'actual_value' => $itemData['new_actual_value'],
                        // Opcional: Atualizar incerteza se tiver campo no repeater
                        'calibration_due' => $this->record->calibration_date->copy()->addMonths(24), // Herda data
                        'status' => 'active',
                    ]);
                }
            }
        }

        if ($this->record->result === 'rejected') {
            Notification::make()
                ->warning()
                ->title('Atenção: Instrumento Reprovado')
                ->body('O desvio encontrado foi superior à incerteza/critério permitido. O status foi definido como "Reprovado" automaticamente.')
                ->persistent() // O usuário precisa fechar o alerta
                ->send();
        }
    }

}
