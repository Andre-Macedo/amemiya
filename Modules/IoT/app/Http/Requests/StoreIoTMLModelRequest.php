<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para registro de modelos de Machine Learning no Model Registry.
 */
class StoreIoTMLModelRequest extends FormRequest
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
            'model_type' => ['required', 'string', 'in:xgboost_cloud,iforest_edge'],
            'version' => ['required', 'string', 'max:50'],
            'target_device' => ['required', 'string', 'in:cloud,edge_esp32'],
            'dataset_id' => ['nullable', 'string', 'exists:iot_ml_datasets,id'],
            'status' => ['nullable', 'string', 'in:candidate,shadow,in_production,deprecated'],
            'artifact_path' => ['nullable', 'string', 'max:500'],
            'metrics' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
