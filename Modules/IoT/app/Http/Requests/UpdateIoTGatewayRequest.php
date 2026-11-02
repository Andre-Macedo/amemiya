<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para atualização de gateways de telemetria IoT.
 */
class UpdateIoTGatewayRequest extends FormRequest
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
        $gatewayId = $this->route('iot_gateway')?->id ?? $this->route('iot_gateway');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'device_id' => ['sometimes', 'required', 'string', 'max:100', 'unique:iot_gateways,device_id,'.$gatewayId],
            'station_id' => ['nullable', 'string', 'exists:stations,id'],
            'status' => ['sometimes', 'required', 'string', 'in:online,offline,maintenance,unpaired'],
        ];
    }
}
