<?php

declare(strict_types=1);

namespace Modules\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a new system station.
 */
class StoreStationRequest extends FormRequest
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
            'location' => ['nullable', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
        ];
    }
}
