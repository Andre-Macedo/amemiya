<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\System\Models\Setting;

/**
 * Validates the calibration submission data.
 */
class StoreCalibrationRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth handled by Sanctum middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Returns:
     *     An array of validation rules.
     */
    public function rules(): array
    {
        return [
            'instrument_id' => ['required', 'exists:instruments,id'],
            'checklist_template_id' => ['required', 'exists:checklist_templates,id'],
            'provider_id' => ['nullable', 'exists:suppliers,id'],
            'calibration_date' => ['nullable', 'date'],
            'result' => ['required', 'string'],
            'password' => ['required', 'string'],
            'items' => ['nullable', 'array'],
            'environment.temperature' => ['nullable', 'numeric'],
            'environment.humidity' => ['nullable', 'numeric'],
            'deviation' => ['nullable', 'numeric'],
            'as_found_deviation' => ['nullable', 'numeric'],
            'as_left_deviation' => ['nullable', 'numeric'],
            'uncertainty' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'as_found_result' => ['nullable', 'string'],
            'as_left_result' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // 1. Validação de Competência (Já existente)
            if ($this->instrument_id) {
                $isStrict = Setting::getValue('strict_competence_enforcement', 'false') === 'true';
                if ($isStrict) {
                    $instrument = Instrument::find($this->instrument_id);
                    if ($instrument && $instrument->instrument_type_id) {
                        $user = $this->user();
                        if (method_exists($user, 'hasValidCompetenceFor') && ! $user->hasValidCompetenceFor($instrument->instrument_type_id)) {
                            $validator->errors()->add('instrument_id', 'Unauthorized: You do not have valid competence/training to calibrate this type of instrument.');
                        }
                    }
                }
            }

            // 2. Interlock de Padrões de Referência (ISO 17025)
            $this->validateReferenceStandards($validator);
        });
    }

    protected function validateReferenceStandards($validator): void
    {
        // Coleta todos os IDs de padrões enviados (no cabeçalho ou nos itens do checklist)
        $standardIds = collect();

        if ($this->input('standard_id')) {
            $standardIds->push($this->input('standard_id'));
        }

        if (is_array($this->input('items'))) {
            foreach ($this->input('items') as $item) {
                if (! empty($item['reference_standard_id'])) {
                    $standardIds->push($item['reference_standard_id']);
                }
            }
        }

        $standardIds = $standardIds->unique()->filter();

        if ($standardIds->isEmpty()) {
            return;
        }

        $standards = ReferenceStandard::whereIn('id', $standardIds)->get();

        foreach ($standards as $standard) {
            // Regra A: Status deve ser Ativo
            if ($standard->status !== ItemStatus::Active) {
                $validator->errors()->add('items', "O padrão '{$standard->name}' não pode ser utilizado pois seu status atual é '{$standard->status->getLabel()}'.");
            }

            // Regra B: Não pode estar vencido
            if ($standard->calibration_due && $standard->calibration_due->isPast()) {
                $validator->errors()->add('items', "O padrão '{$standard->name}' está com a calibração VENCIDA (Vencimento: {$standard->calibration_due->format('d/m/Y')}). Uso bloqueado.");
            }
        }
    }
}
