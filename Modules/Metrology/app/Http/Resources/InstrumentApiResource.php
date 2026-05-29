<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\System\Http\Resources\StationApiResource;

/**
 * Serializes Instrument models for API responses.
 */
class InstrumentApiResource extends JsonResource
{
    /**
     * Transforms the instrument into an associative array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the instrument.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'serial_number' => $this->serial_number,
            'stock_number' => $this->stock_number,
            'instrument_type_id' => $this->instrument_type_id,
            'instrument_type' => $this->instrumentType ? $this->instrumentType->name : 'N/A',
            'type' => $this->instrumentType ? $this->instrumentType->name : 'N/A',
            'manufacturer' => $this->manufacturer ?? 'Unknown',
            'model' => $this->model ?? $this->stock_number ?? 'N/A',
            'status' => $this->status,
            'location' => $this->station ? $this->station->name : $this->location,
            'station_id' => $this->current_station_id,
            'range' => $this->measuring_range,
            'precision' => $this->resolution ?? 'N/A',
            'mpe' => $this->mpe,

            // Dates
            'acquisition_date' => $this->acquisition_date?->toDateString(),
            'calibration_due' => $this->calibration_due?->toDateString(),
            'next_calibration_date' => $this->calibration_due?->toDateString(),
            'last_calibration_date' => $this->calibrations->first()?->calibration_date?->toDateString(),

            // Metadata
            'nfc_tag' => $this->nfc_tag,
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'current_station_id' => $this->current_station_id,
            'material_id' => $this->material_id,
            'calibration_frequency' => (int) $this->getCalibrationFrequencyMonths(),

            // Relationships
            'station' => new StationApiResource($this->whenLoaded('station')),
            'calibrations' => CalibrationApiResource::collection($this->whenLoaded('calibrations')),
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
