<?php

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class InstrumentApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request)
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'serial_number' => $this->serial_number,
            'instrument_type' => $this->instrumentType ? $this->instrumentType->name : 'N/A',
            'status' => $this->status,
            'location' => $this->location,
            'precision' => $this->resolution ?? 'N/A',
            'acquisition_date' => $this->acquisition_date,
            'calibration_due' => $this->calibration_due,
            'nfc_tag' => $this->nfc_tag,
            'current_station_id' => $this->current_station_id,

            'station' => new StationApiResource($this->whenLoaded('station')),
            'calibrations' => CalibrationApiResource::collection($this->whenLoaded('calibrations')),
        ];
    }
}
