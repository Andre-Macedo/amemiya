<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Standardizes the JSON output for Calibration entities.
 */
class CalibrationApiResource extends JsonResource
{
    /**
     * Maps database results to user-friendly labels.
     *
     * Args:
     *     result: The raw result value from the database.
     *
     * Returns:
     *     A string label for display.
     */
    protected function getResultLabel($result): string
    {
        return match ($result) {
            'approved' => 'Aprovado',
            'approved_with_restrictions' => 'Aprovado com Restrições',
            'rejected' => 'Reprovado',
            'in_calibration' => 'Em Andamento',
            default => 'Desconhecido',
        };
    }

    /**
     * Maps database results to standardized frontend codes.
     *
     * Args:
     *     result: The raw result value from the database.
     *
     * Returns:
     *     A string code (pass/fail/etc).
     */
    protected function getResultCode($result): string
    {
        return match ($result) {
            'approved' => 'pass',
            'approved_with_restrictions' => 'conditional_pass',
            'rejected' => 'fail',
            'in_calibration' => 'unknown',
            default => 'unknown',
        };
    }

    /**
     * Transforms the resource into an array for API consumption.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     An associative array of serialized calibration data.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'status' => $this->status,

            // Calibrated Item Identity
            'calibrated_item_id' => (string) $this->calibrated_item_id,
            'calibrated_item_name' => $this->calibratedItem ? $this->calibratedItem->name : 'Item Desconhecido',
            'calibrated_item_type' => $this->calibrated_item_type,
            'calibrated_item' => $this->calibratedItem ? [
                'id' => (string) $this->calibratedItem->id,
                'name' => $this->calibratedItem->name,
                'type' => class_basename($this->calibrated_item_type),
                'manufacturer' => $this->calibratedItem->manufacturer ?? 'Unknown',
                'model' => $this->calibratedItem->model ?? $this->calibratedItem->stock_number ?? 'N/A',
                'serial_number' => $this->calibratedItem->serial_number ?? 'N/A',
                'stock_number' => $this->calibratedItem->stock_number ?? 'N/A',
                'mpe' => $this->calibratedItem->mpe ?? 'N/A',
                'mpe_value' => (float) ($this->calibratedItem->mpe_value ?? 0),
                'resolution' => $this->calibratedItem->resolution ?? 'N/A',
                'range' => $this->calibratedItem->measuring_range ?? 'N/A',
            ] : null,

            // Standardized Results
            'result' => $this->getResultCode($this->result instanceof \UnitEnum ? $this->result->value : $this->result),
            'result_label' => $this->getResultLabel($this->result instanceof \UnitEnum ? $this->result->value : $this->result),
            'status_key' => $this->result instanceof \UnitEnum ? $this->result->value : $this->result,
            'decision_rule' => 'Simple Acceptance (ISO 14253-1)',

            // Date Formatting
            'date' => $this->calibration_date ? Carbon::parse($this->calibration_date)->format('Y-m-d') : null,
            'calibration_date' => $this->calibration_date ? Carbon::parse($this->calibration_date)->format('d/m/Y') : 'N/A',
            'next_due_date' => $this->calibratedItem && $this->calibratedItem->calibration_due
                ? Carbon::parse($this->calibratedItem->calibration_due)->format('Y-m-d')
                : null,
            'next_calibration_due' => $this->calibratedItem && $this->calibratedItem->calibration_due
                ? Carbon::parse($this->calibratedItem->calibration_due)->format('d/m/Y')
                : null,

            // Personnel
            'checklist_id' => (string) ($this->checklist_id ?? ''),
            'technician' => $this->performedBy ? $this->performedBy->name : 'Sistema/Externo',
            'performed_by' => $this->performedBy ? $this->performedBy->name : 'Sistema/Externo',

            // Technical Data
            'deviation' => (float) $this->deviation,
            'as_found_deviation' => (float) $this->as_found_deviation,
            'as_left_deviation' => (float) $this->as_left_deviation,
            'uncertainty' => (float) $this->uncertainty,
            'k_factor' => (float) ($this->k_factor ?? 2.00),
            'as_found_result' => $this->as_found_result,
            'as_left_result' => $this->as_left_result,
            'uncertainty_budget' => $this->calculation_data ?? [],
            'temperature' => (float) $this->temperature,
            'humidity' => (float) $this->humidity,
            'notes' => $this->notes,

            // Generated Content
            'certificate_url' => route('api.calibrations.pdf', ['calibration' => $this->id]),

            // Detailed Checklist Items
            'checklist_items' => $this->checklist ? $this->checklist->items->map(function ($item) {
                $asFoundReadings = $item->as_found_readings;
                if (is_string($asFoundReadings)) {
                    $decoded = json_decode($asFoundReadings, true);
                    $asFoundReadings = is_array($decoded) ? $decoded : [];
                }

                $asLeftReadings = $item->as_left_readings;
                if (is_string($asLeftReadings)) {
                    $decoded = json_decode($asLeftReadings, true);
                    $asLeftReadings = is_array($decoded) ? $decoded : [];
                }

                return [
                    'step' => $item->step,
                    'question_type' => $item->question_type,
                    'as_found_readings' => is_array($asFoundReadings) ? $asFoundReadings : [],
                    'as_left_readings' => is_array($asLeftReadings) ? $asLeftReadings : [],
                    'adjusted' => (bool) $item->adjusted,
                    'readings_formatted' => empty($asFoundReadings) ? '-' : implode(' | ', (array) $asFoundReadings),
                    'nominal_value' => (float) $item->nominal_value,
                    'error' => $item->question_type === 'numeric' && ! empty($asFoundReadings)
                        ? (array_sum($asFoundReadings) / count($asFoundReadings)) - (float) $item->nominal_value
                        : null,
                    'reference_standard' => $item->referenceStandard ? [
                        'id' => $item->referenceStandard->id,
                        'name' => $item->referenceStandard->name,
                        'serial_number' => $item->referenceStandard->serial_number,
                    ] : null,
                    'result' => $item->result,
                    'uncertainty' => $item->uncertainty,
                    'notes' => $item->notes,
                ];
            }) : [],
        ];
    }
}
