<?php

declare(strict_types=1);

namespace Modules\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para atualização de máquinas e ativos industriais.
 */
class UpdateMachineRequest extends FormRequest
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
        $machineId = $this->route('machine')?->id ?? $this->route('machine');

        return [
            'station_id' => ['nullable', 'string', 'exists:stations,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', 'unique:machines,code,'.$machineId],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'required', 'string', 'in:operational,maintenance,decommissioned'],
            'metadata' => ['nullable', 'array'],
            'metadata.iso_group' => ['nullable', 'string', 'in:group_1,group_2,group_3,group_4'],
            'metadata.foundation_type' => ['nullable', 'string', 'in:rigid,flexible'],
        ];
    }
}
