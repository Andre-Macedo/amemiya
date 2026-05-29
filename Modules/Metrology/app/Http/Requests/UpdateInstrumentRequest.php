<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the update of an existing metrology instrument.
 */
class UpdateInstrumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Returns:
     *     An array of validation rules.
     */
    public function rules(): array
    {
        $instrumentId = $this->route('instrument')?->id ?? $this->route('instrument');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'serial_number' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('instruments', 'serial_number')->ignore($instrumentId),
            ],
            'stock_number' => ['nullable', 'string', 'max:255'],
            'instrument_type_id' => ['sometimes', 'required', 'exists:instrument_types,id'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string'],
            'status' => ['sometimes', 'required', 'string', 'in:active,inactive,maintenance,calibrating,lost'],
            'acquisition_date' => ['nullable', 'date'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'mpe' => ['nullable', 'string'],
            'measuring_range' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
        ];
    }
}
