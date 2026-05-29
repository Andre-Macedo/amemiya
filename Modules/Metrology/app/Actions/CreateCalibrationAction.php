<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Metrology\DTOs\CalibrationSubmissionDTO;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\ChecklistTemplateItem;
use Modules\Metrology\Models\Instrument;

/**
 * Encapsulates the logic for creating a complete calibration record.
 */
class CreateCalibrationAction
{
    /**
     * Executes the calibration creation process.
     *
     * Args:
     *     dto: The validated calibration data.
     *
     * Returns:
     *     The newly created Calibration model.
     *
     * Throws:
     *     \Exception if any part of the process fails.
     */
    public function execute(CalibrationSubmissionDTO $dto): Calibration
    {
        return DB::transaction(function () use ($dto) {
            // 1. Create Calibration Header
            $calibration = Calibration::create([
                'calibrated_item_type' => Instrument::class,
                'calibrated_item_id' => $dto->instrumentId,
                'checklist_id' => null,
                'calibration_date' => $dto->date,
                'type' => 'internal',
                'result' => $dto->result,
                'as_found_result' => $dto->asFoundResult,
                'as_left_result' => $dto->asLeftResult,
                'temperature' => $dto->temperature,
                'humidity' => $dto->humidity,
                'deviation' => $dto->deviation,
                'as_found_deviation' => $dto->asFoundDeviation,
                'as_left_deviation' => $dto->asLeftDeviation,
                'uncertainty' => $dto->uncertainty,
                'notes' => $dto->notes,
                'performed_by_id' => $dto->performedBy,
                'status' => 'in_review',
            ]);

            // 2. Create Checklist instance
            $checklist = Checklist::create([
                'calibration_id' => $calibration->id,
                'checklist_template_id' => $dto->templateId,
                'completed' => true,
            ]);

            // 3. Process Items based on Template
            $templateItems = ChecklistTemplateItem::where('checklist_template_id', $dto->templateId)
                ->orderBy('order')
                ->get();

            $appItemsMap = collect($dto->items)->keyBy('item_id');
            $checklistItemsData = [];

            foreach ($templateItems as $templateItem) {
                $appResponse = $appItemsMap->get($templateItem->id);

                $readingsJson = null;
                $resultItem = null;
                $notesItem = null;
                $standardId = null;
                $isCompleted = false;

                if ($appResponse) {
                    if ($templateItem->question_type === 'numeric') {
                        if (! empty($appResponse['readings'])) {
                            $rawReadings = is_array($appResponse['readings']) ? $appResponse['readings'] : [$appResponse['readings']];
                            $readingsJson = json_encode($rawReadings);
                            $isCompleted = true;
                        }
                        if (isset($appResponse['reference_standard_id']) && is_numeric($appResponse['reference_standard_id'])) {
                            $standardId = (int) $appResponse['reference_standard_id'];
                        }
                    } elseif ($templateItem->question_type === 'boolean') {
                        $resultItem = $appResponse['result'] ?? null;
                        if ($resultItem) {
                            $isCompleted = true;
                        }
                    } elseif ($templateItem->question_type === 'text') {
                        $notesItem = $appResponse['notes'] ?? null;
                        if ($notesItem) {
                            $isCompleted = true;
                        }
                    }
                }

                $checklistItemsData[] = [
                    'checklist_id' => $checklist->id,
                    'step' => $templateItem->step,
                    'question_type' => $templateItem->question_type,
                    'order' => $templateItem->order,
                    'required_readings' => $templateItem->required_readings,
                    'completed' => $isCompleted,
                    'readings' => $readingsJson,
                    'result' => $resultItem,
                    'notes' => $notesItem,
                    'reference_standard_id' => $standardId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ChecklistItem::insert($checklistItemsData);

            // 4. Link checklist and trigger model events
            $calibration->update(['checklist_id' => $checklist->id]);
            $calibration->touch();

            return $calibration;
        });
    }
}
