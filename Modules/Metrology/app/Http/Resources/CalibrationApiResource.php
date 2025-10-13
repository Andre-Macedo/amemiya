<?php

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalibrationApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'result' => $this->result,
            'calibration_date' => $this->calibration_date,
            'next_calibration_due' => $this->next_calibration_due,
            'notes' => $this->notes,
        ];
    }
}
