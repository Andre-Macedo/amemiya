<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para atualização de nós sensores industriais IoT.
 */
class UpdateIoTNodeRequest extends FormRequest
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
        $nodeId = $this->route('iot_node')?->id ?? $this->route('iot_node');

        return [
            'gateway_id' => ['sometimes', 'required', 'string', 'exists:iot_gateways,id'],
            'machine_id' => ['sometimes', 'required', 'string', 'exists:machines,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'node_id' => ['sometimes', 'required', 'string', 'max:100', 'unique:iot_nodes,node_id,'.$nodeId],
            'status' => ['sometimes', 'required', 'string', 'in:online,offline,maintenance,unpaired'],
        ];
    }
}
