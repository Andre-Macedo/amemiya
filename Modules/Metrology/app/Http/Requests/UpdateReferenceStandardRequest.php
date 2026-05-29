<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the update of an existing reference standard.
 */
class UpdateReferenceStandardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'stock_number' => ['nullable', 'string', 'max:255'],
            'reference_standard_type_id' => ['sometimes', 'required', 'exists:reference_standard_types,id'],
            'nominal_value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'uncertainty' => ['nullable', 'numeric'],
            'status' => ['sometimes', 'required', 'in:active,inactive,maintenance'],
            'material_id' => ['nullable', 'exists:materials,id'],
        ];
    }
}
