<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para anotação a posteriori de rótulos em logs IoT para treinamento supervisionado.
 */
class LabelIoTLogRequest extends FormRequest
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
            'dataset_id' => ['required', 'string', 'exists:iot_ml_datasets,id'],
            'ground_truth_label' => ['required', 'string', 'in:saudavel,desbalanceamento,folga_mecanica,defeito_rolamento,desligada,anomalia_generica'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
