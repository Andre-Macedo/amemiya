<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkOrderApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'status' => $this->status,
            'item_id' => $this->item_id,
            'item_type' => $this->item_type,
            'item' => $this->item ? [
                'id' => $this->item->id,
                'name' => $this->item->name,
                'serial_number' => $this->item->serial_number,
            ] : null,
            'visual_inspection_notes' => $this->visual_inspection_notes,
            'customer_notes' => $this->customer_notes,
            'expected_return_date' => $this->expected_return_date ? $this->expected_return_date->toDateString() : null,
            'received_by_id' => $this->received_by_id,
            'received_by_name' => $this->receivedBy ? $this->receivedBy->name : null,
            'origin_station_id' => $this->origin_station_id,
            'origin_station_name' => $this->originStation ? $this->originStation->name : null,
            'destination_station_id' => $this->destination_station_id,
            'destination_station_name' => $this->destinationStation ? $this->destinationStation->name : null,
            'courier_name' => $this->courier_name,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
