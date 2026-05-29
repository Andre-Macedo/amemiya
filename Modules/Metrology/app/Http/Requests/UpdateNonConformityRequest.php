<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the update of a non-conformity report.
 */
class UpdateNonConformityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'root_cause_analysis' => ['nullable', 'string'],
            'immediate_action' => ['nullable', 'string'],
            'corrective_action' => ['nullable', 'string'],
            'preventive_action' => ['nullable', 'string'],
            'status' => ['required', 'in:open,investigating,resolved,closed'],
        ];
    }
}
