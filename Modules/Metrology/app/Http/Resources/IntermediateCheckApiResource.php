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
            'reference_standard_id' => $this->reference_standard_id,
            'reference_standard_name' => $this->referenceStandard ? $this->referenceStandard->name : null,
            'performed_by_name' => $this->performer ? $this->performer->name : 'Unknown',
            'temperature' => $this->temperature,
            'humidity' => $this->humidity,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
