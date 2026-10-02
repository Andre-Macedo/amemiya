<?php

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class IntermediateCheckApiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'instrument_id' => (string) $this->instrument_id,
            'check_date' => $this->check_date->format('Y-m-d'),
            'result' => $this->result,
            'nominal_value' => $this->nominal_value !== null ? (float) $this->nominal_value : null,
            'measured_value' => $this->measured_value !== null ? (float) $this->measured_value : null,
            'deviation' => $this->deviation !== null ? (float) $this->deviation : null,
            'reference_standard_id' => $this->reference_standard_id,
            'reference_standard_name' => $this->referenceStandard ? $this->referenceStandard->name : null,
            'performed_by_name' => $this->performer ? $this->performer->name : 'Unknown',
            'temperature' => $this->temperature !== null ? (float) $this->temperature : null,
            'humidity' => $this->humidity !== null ? (float) $this->humidity : null,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
