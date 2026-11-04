<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\Machine;

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
            'item_type' => ['nullable', 'string'],
            'visual_inspection_notes' => ['nullable', 'string'],
            'customer_notes' => ['nullable', 'string'],
            'expected_return_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:received,in_queue,calibrating,finished,dispatched'],
            'photo' => ['nullable', 'file', 'max:10240'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $itemType = $this->input('item_type');

        if ($itemType === 'machine' || $itemType === 'Machine') {
            $this->merge(['item_type' => Machine::class]);
        } elseif ($itemType === 'instrument' || $itemType === 'Instrument' || empty($itemType)) {
            $this->merge(['item_type' => Instrument::class]);
        }
    }
}
