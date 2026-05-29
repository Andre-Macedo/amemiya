<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes ReferenceStandard models for API responses.
 */
class ReferenceStandardApiResource extends JsonResource
{
    /**
     * Transforms the standard into an associative array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the standard.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'serial_number' => $this->serial_number ?? 'N/A',
            'stock_number' => $this->stock_number ?? 'N/A',
            'type' => $this->referenceStandardType ? $this->referenceStandardType->name : 'other',
            'nominal_value' => $this->nominal_value,
            'actual_value' => $this->actual_value ?? $this->nominal_value,
            'unit' => $this->unit,
            'uncertainty' => $this->uncertainty,
            'status' => $this->status,
            'manufacturer' => $this->manufacturer,
            'certificate_url' => $this->active_certificate_url,

            // Dates
            'last_calibration_date' => $this->last_calibration_date?->toDateString(),
            'next_calibration_date' => $this->next_calibration_date?->toDateString(),

            // Metadata
            'material_id' => $this->material_id,
            'effective_serial_number' => $this->effective_serial_number,
            'effective_stock_number' => $this->effective_stock_number,

            // Relationships
            'parent_id' => $this->parent_id,
            'parent' => new ReferenceStandardApiResource($this->whenLoaded('parent')),
            'children' => ReferenceStandardApiResource::collection($this->whenLoaded('children')),
            'open_non_conformity' => $this->whenLoaded('openNonConformity'),
            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'file_name' => $attachment->file_name,
                        'original_name' => $attachment->original_name,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->size,
                        'url' => asset('storage/'.$attachment->file_path),
                        'created_at' => $attachment->created_at,
                    ];
                });
            }),
        ];
    }
}
