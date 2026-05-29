<?php

declare(strict_types=1);

namespace Modules\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a new system supplier.
 */
class StoreSupplierRequest extends FormRequest
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
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:255', 'unique:suppliers,cnpj'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'is_manufacturer' => ['boolean'],
            'is_calibration_provider' => ['boolean'],
            'is_maintenance_provider' => ['boolean'],
            'rbc_code' => ['nullable', 'string', 'max:255'],
            'accreditation_valid_until' => ['nullable', 'date'],
        ];
    }
}
