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

            $appItemsMap = collect($dto->items)->keyBy(function ($item) {
                return $item['template_item_id'] ?? $item['item_id'] ?? $item['id'] ?? $item['step'] ?? null;
            });
            $checklistItemsData = [];

            foreach ($templateItems as $templateItem) {
                $appResponse = $appItemsMap->get($templateItem->id) ?? $appItemsMap->get($templateItem->step);

                $asFoundReadings = null;
                $asLeftReadings = null;
                $adjusted = false;
                $resultItem = null;
                $notesItem = null;
                $standardId = null;
                $isCompleted = false;

                if ($appResponse) {
                    if ($templateItem->question_type === 'numeric') {
                        $rawFound = $appResponse['as_found_readings'] ?? $appResponse['readings'] ?? null;
                        if (! empty($rawFound)) {
                            $asFoundReadings = is_array($rawFound) ? $rawFound : [$rawFound];
                            $isCompleted = true;
                        }

                        $rawLeft = $appResponse['as_left_readings'] ?? null;
                        if (! empty($rawLeft)) {
                            $asLeftReadings = is_array($rawLeft) ? $rawLeft : [$rawLeft];
                        }

                        $adjusted = (bool) ($appResponse['adjusted'] ?? false);

                        $possibleStandardId = $appResponse['standard_id'] ?? $appResponse['reference_standard_id'] ?? null;
                        if (! empty($possibleStandardId)) {
                            $standardId = (string) $possibleStandardId;
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

                $checklist->items()->create([
                    'step' => $templateItem->step,
                    'nominal_value' => $templateItem->nominal_value,
                    'question_type' => $templateItem->question_type,
                    'order' => $templateItem->order,
                    'required_readings' => $templateItem->required_readings,
                    'completed' => $isCompleted,
                    'as_found_readings' => $asFoundReadings,
                    'as_left_readings' => $asLeftReadings,
                    'adjusted' => $adjusted,
                    'result' => $resultItem,
                    'notes' => $notesItem,
                    'reference_standard_id' => $standardId,
                ]);
            }

            // 4. Link checklist and trigger model events
            $calibration->checklist_id = $checklist->id;
            $calibration->saveQuietly();
            $calibration->setRelation('checklist', $checklist);

            return $calibration;
        });
    }
}
