<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a new checklist template (procedure).
 */
class StoreChecklistTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'instrument_type_id' => ['required', 'exists:instrument_types,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.step' => ['required', 'string'],
            'items.*.question_type' => ['required', 'in:numeric,boolean,text'],
            'items.*.order' => ['required', 'integer'],
            'items.*.required_readings' => ['nullable', 'integer', 'min:1'],
            'items.*.nominal_value' => ['nullable', 'numeric'],
            'items.*.criteria' => ['nullable', 'numeric'],
            'items.*.reference_standard_type_id' => ['nullable', 'exists:reference_standard_types,id'],
        ];
    }
}
