<?php

declare(strict_types=1);

namespace Modules\System\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para cadastro de novas máquinas e ativos industriais.
 */
class StoreMachineRequest extends FormRequest
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
            'station_id' => ['nullable', 'string', 'exists:stations,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:machines,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:operational,maintenance,decommissioned'],
            'metadata' => ['nullable', 'array'],
            'metadata.iso_group' => ['nullable', 'string', 'in:group_1,group_2,group_3,group_4'],
            'metadata.foundation_type' => ['nullable', 'string', 'in:rigid,flexible'],
        ];
    }
}
