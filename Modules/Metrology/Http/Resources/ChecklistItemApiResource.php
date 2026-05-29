<?php

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistItemApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'question_type' => $this->question_type,
            'options' => $this->options,
            'order' => $this->order,
            'is_mandatory' => $this->is_mandatory,
            'nominal_value' => $this->nominal_value,
            'upper_tolerance' => $this->upper_tolerance,
            'lower_tolerance' => $this->lower_tolerance,
            'standard_id' => $this->standard_id,
        ];
    }
}
