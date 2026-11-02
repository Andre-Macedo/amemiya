<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para registro de checagens intermediárias (ISO/IEC 17025 Cláusula 6.4.10).
 */
class StoreIntermediateCheckRequest extends FormRequest
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
            'instrument_id' => ['required', 'string', 'exists:instruments,id'],
            'check_date' => ['required', 'date'],
            'result' => ['required', 'string', 'in:passed,failed'],
            'nominal_value' => ['nullable', 'numeric'],
            'measured_value' => ['nullable', 'numeric'],
            'deviation' => ['nullable', 'numeric'],
            'reference_standard_id' => ['nullable', 'string', 'exists:reference_standards,id'],
            'performed_by' => ['nullable', 'string', 'exists:users,id'],
            'temperature' => ['nullable', 'numeric'],
            'humidity' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
