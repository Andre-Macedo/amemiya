<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:scheduled,received,in_queue,calibrating,finished,dispatched'],
            'visual_inspection_notes' => ['nullable', 'string'],
            'customer_notes' => ['nullable', 'string'],
            'expected_return_date' => ['nullable', 'date'],
            'origin_station_id' => ['nullable', 'exists:stations,id'],
            'destination_station_id' => ['nullable', 'exists:stations,id'],
            'courier_name' => ['nullable', 'string', 'max:255'],
            'dispatched_at' => ['nullable', 'date'],
        ];
    }
}
