<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a new metrology instrument.
 */
class StoreInstrumentRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255', 'unique:instruments,serial_number'],
            'stock_number' => ['nullable', 'string', 'max:255'],
            'instrument_type_id' => ['required', 'exists:instrument_types,id'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:active,inactive,maintenance,calibrating,lost'],
            'acquisition_date' => ['nullable', 'date'],
            'material_id' => ['nullable', 'exists:materials,id'],
            'mpe' => ['nullable', 'string'],
            'measuring_range' => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
        ];
    }
}
