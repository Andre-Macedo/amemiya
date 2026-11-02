<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a non-conformity report (ISO/IEC 17025 Cláusula 7.10).
 */
class StoreNonConformityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>|string>
     */
    public function rules(): array
    {
        return [
            'item_type' => ['required', 'string', 'max:255'],
            'item_id' => ['required', 'string'],
            'calibration_id' => ['nullable', 'string', 'exists:calibrations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'immediate_action' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
