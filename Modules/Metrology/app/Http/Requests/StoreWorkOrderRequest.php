<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'string'],
            'item_type' => ['required', 'string'],
            'visual_inspection_notes' => ['nullable', 'string'],
            'customer_notes' => ['nullable', 'string'],
            'expected_return_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:received,in_queue,calibrating,finished,dispatched'],
        ];
    }
}
