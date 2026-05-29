<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Metrology\Models\Material;

/**
 * Validates the update of an existing material.
 */
class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $material = $this->route('material');
        $materialId = $material instanceof Material ? $material->id : (int) $material;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('materials', 'name')->ignore($materialId),
            ],
            'cte' => ['nullable', 'numeric'],
            'category' => ['nullable', 'string', 'max:255'],
        ];
    }
}
