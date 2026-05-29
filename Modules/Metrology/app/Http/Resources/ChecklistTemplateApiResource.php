<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes ChecklistTemplate (procedure) models for API responses.
 */
class ChecklistTemplateApiResource extends JsonResource
{
    /**
     * Transforms the procedure template into an associative array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the procedure template.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'instrument_type_id' => $this->instrument_type_id,
            'instrument_type' => $this->instrumentType ? $this->instrumentType->name : 'N/A',

            // Nested Items
            'items' => $this->items->map(fn ($item) => [
                'id' => (string) $item->id,
                'step' => $item->step,
                'question_type' => $item->question_type,
                'order' => $item->order,
                'required_readings' => $item->required_readings,
                'nominal_value' => $item->nominal_value,
                'criteria' => $item->criteria,
                'reference_standard_type_id' => $item->reference_standard_type_id,
            ]),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
