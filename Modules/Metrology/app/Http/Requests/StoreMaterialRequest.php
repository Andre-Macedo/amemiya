<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the creation of a new material.
 */
class StoreMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:materials,name'],
            'cte' => ['nullable', 'numeric'],
            'category' => ['nullable', 'string', 'max:255'],
        ];
    }
}
