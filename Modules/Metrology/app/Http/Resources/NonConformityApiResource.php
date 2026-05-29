<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes NonConformity models for API responses.
 */
class NonConformityApiResource extends JsonResource
{
    /**
     * Transforms the resource into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     An associative array of serialized non-conformity data.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'severity' => $this->severity,

            // Analysis and Actions
            'root_cause_analysis' => $this->root_cause_analysis,
            'immediate_action' => $this->immediate_action,
            'corrective_action' => $this->corrective_action,
            'preventive_action' => $this->preventive_action,

            // Item Identity (Polymorphic)
            'item_id' => (string) $this->item_id,
            'item_type' => class_basename($this->item_type),
            'item_name' => $this->item ? $this->item->name : 'N/A',

            // Dates
            'opened_at' => $this->created_at?->toDateTimeString(),
            'closed_at' => $this->closed_at?->toDateTimeString(),

            // Personnel
            'opened_by' => $this->opener ? $this->opener->name : 'System',
            'closed_by' => $this->closer ? $this->closer->name : null,

            // Relationships
            'calibration_id' => $this->calibration_id,
            'calibration' => new CalibrationApiResource($this->whenLoaded('calibration')),
        ];
    }
}
