<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstrumentTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'calibration_frequency_months' => ['nullable', 'integer', 'min:1'],
            'decision_rule' => ['nullable', 'string', 'max:100'],
        ];
    }
}
