<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para cadastro de nós sensores industriais IoT.
 */
class StoreIoTNodeRequest extends FormRequest
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
            'gateway_id' => ['required', 'string', 'exists:iot_gateways,id'],
            'machine_id' => ['required', 'string', 'exists:machines,id'],
            'name' => ['required', 'string', 'max:255'],
            'node_id' => ['required', 'string', 'max:100', 'unique:iot_nodes,node_id'],
            'status' => ['required', 'string', 'in:online,offline,maintenance,unpaired'],
        ];
    }
}
