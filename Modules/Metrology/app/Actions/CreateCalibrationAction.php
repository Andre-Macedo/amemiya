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
    public function __construct(
        protected ?CalculateCalibrationUncertaintyAction $uncertaintyAction = null
    ) {
        $this->uncertaintyAction = $uncertaintyAction ?? app(CalculateCalibrationUncertaintyAction::class);
    }

    /**
     * Executes the calibration creation process.
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

            $calculatedMaxFoundDev = 0.0;
            $calculatedMaxLeftDev = 0.0;
            $hasCalculatedDev = false;

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
                            $asFoundReadings = is_array($rawFound) ? array_map('floatval', $rawFound) : [(float) $rawFound];
                            $isCompleted = true;

                            $nominal = $templateItem->nominal_value;
                            if ($nominal === null) {
                                $clean = preg_replace('/[^0-9.]/', '', (string) $templateItem->step);
                                $nominal = $clean !== '' ? (float) $clean : null;
                            }

                            if ($nominal !== null && count($asFoundReadings) > 0) {
                                $meanFound = array_sum($asFoundReadings) / count($asFoundReadings);
                                $devFound = $meanFound - $nominal;
                                if (abs($devFound) >= abs($calculatedMaxFoundDev)) {
                                    $calculatedMaxFoundDev = $devFound;
                                    $hasCalculatedDev = true;
                                }
                            }
                        }

                        $rawLeft = $appResponse['as_left_readings'] ?? null;
                        if (! empty($rawLeft)) {
                            $asLeftReadings = is_array($rawLeft) ? array_map('floatval', $rawLeft) : [(float) $rawLeft];

                            $nominal = $templateItem->nominal_value;
                            if ($nominal === null) {
                                $clean = preg_replace('/[^0-9.]/', '', (string) $templateItem->step);
                                $nominal = $clean !== '' ? (float) $clean : null;
                            }

                            if ($nominal !== null && count($asLeftReadings) > 0) {
                                $meanLeft = array_sum($asLeftReadings) / count($asLeftReadings);
                                $devLeft = $meanLeft - $nominal;
                                if (abs($devLeft) >= abs($calculatedMaxLeftDev)) {
                                    $calculatedMaxLeftDev = $devLeft;
                                }
                            }
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

            // 4. Canonical server-side recalculation of deviations and GUM uncertainty
            if ($hasCalculatedDev) {
                $calibration->as_found_deviation = round($calculatedMaxFoundDev, 4);
                $calibration->as_left_deviation = ($calculatedMaxLeftDev !== 0.0) ? round($calculatedMaxLeftDev, 4) : null;
                $calibration->deviation = ($calculatedMaxLeftDev !== 0.0) ? round($calculatedMaxLeftDev, 4) : round($calculatedMaxFoundDev, 4);
            }

            try {
                $uncertaintyResult = $this->uncertaintyAction?->execute([
                    'instrument_id' => $dto->instrumentId,
                    'checklist_template_id' => $dto->templateId,
                    'temperature' => $dto->temperature,
                    'items' => $dto->items,
                ]);

                if (! empty($uncertaintyResult['uncertainty']) && $uncertaintyResult['uncertainty'] > 0) {
                    $calibration->uncertainty = $uncertaintyResult['uncertainty'];
                }
            } catch (\Throwable) {
                // Keep DTO uncertainty as fallback
            }

            // 5. Link checklist and trigger model events
            $calibration->checklist_id = $checklist->id;
            $calibration->saveQuietly();
            $calibration->setRelation('checklist', $checklist);

            return $calibration;
        });
    }
}

