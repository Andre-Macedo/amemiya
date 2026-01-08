<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;

class CreateChecklistAction
{
    /**
     * Cria um checklist e seus itens baseados no template.
     *
     * @param Calibration $calibration
     * @param array{template_id: int, items: array} $checklistData
     * @return Checklist
     */
    public function execute(Calibration $calibration, array $checklistData): Checklist
    {
        $checklist = Checklist::create([
            'calibration_id' => $calibration->id,
            'checklist_template_id' => $checklistData['template_id'],
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
                'completed' => $isCompleted,
                'readings' => isset($item['readings']) ? json_encode(array_column($item['readings'], 'value')) : null,
                'uncertainty' => $item['uncertainty'] ?? null,
                'result' => $item['result'] ?? null,
                'notes' => $item['notes'] ?? null,
                'reference_standard_id' => $item['reference_standard_id'] ?? null,
            ];
        }, $checklistData['items']);

        ChecklistItem::insert($items);

        $calibration->update(['checklist_id' => $checklist->id]);

        return $checklist;
    }
}
