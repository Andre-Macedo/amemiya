<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Str;
use Modules\Metrology\DTOs\ChecklistCreationData;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;

class CreateChecklistAction
{
    /**
     * Cria um checklist e seus itens baseados no template e dados fornecidos.
     * Utiliza DTO para garantir a estrutura dos dados de entrada.
     */
    public function execute(Calibration $calibration, ChecklistCreationData|array $checklistData): Checklist
    {
        if (is_array($checklistData)) {
            $checklistData = ChecklistCreationData::fromArray($checklistData);
        }

        $checklist = Checklist::create([
            'calibration_id' => $calibration->id,
            'checklist_template_id' => $checklistData->templateId,
            'completed' => false,
        ]);

        $items = array_map(function ($item) use ($checklist) {
            $hasResult = ! empty($item->result);
            $hasAsFoundReadings = ! empty($item->asFoundReadings) && isset($item->asFoundReadings[0]['value']);
            $isCompleted = $hasResult || $hasAsFoundReadings;

            return [
                'id' => (string) Str::ulid(),
                'tenant_id' => $checklist->tenant_id,
                'checklist_id' => $checklist->id,
                'step' => $item->step,
                'question_type' => $item->questionType,
                'order' => $item->order,
                'required_readings' => $item->requiredReadings,
                'completed' => $isCompleted,
                'as_found_readings' => ! empty($item->asFoundReadings) ? json_encode(array_column($item->asFoundReadings, 'value')) : null,
                'as_left_readings' => ! empty($item->asLeftReadings) ? json_encode(array_column($item->asLeftReadings, 'value')) : null,
                'adjusted' => $item->adjusted,
                'uncertainty' => $item->uncertainty,
                'result' => $item->result,
                'notes' => $item->notes,
                'reference_standard_id' => $item->referenceStandardId,
            ];
        }, $checklistData->items);

        ChecklistItem::insert($items);

        $calibration->checklist_id = $checklist->id;
        $calibration->saveQuietly();

        return $checklist;
    }
}
