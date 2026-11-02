<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para criação de datasets de MLOps no cluster IoT.
 */
class StoreIoTMLDatasetRequest extends FormRequest
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
            'type' => [
                'required',
                'string',
                'in:diagnostic_multiclass,baseline_normal,benchmark_golden_set,run_to_failure,supervised_xgboost,unsupervised_iforest',
            ],
            'target_machine_id' => ['nullable', 'string', 'exists:machines,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
