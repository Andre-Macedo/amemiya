<?php

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstrumentApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'serial_number' => $this->serial_number,
            'stock_number' => $this->stock_number,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'status' => $this->status,
            'station' => $this->whenLoaded('station'),
            'type' => $this->whenLoaded('instrumentType'),
            'last_calibration_date' => $this->calibrations->sortByDesc('calibration_date')->first()?->calibration_date,
            'next_calibration_date' => $this->next_calibration_date,
            'image_url' => $this->image_path ? url('storage/'.$this->image_path) : null,
        ];
    }
}
