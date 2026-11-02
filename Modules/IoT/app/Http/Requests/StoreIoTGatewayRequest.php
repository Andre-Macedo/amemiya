<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação para cadastro de novos gateways de telemetria IoT.
 */
class StoreIoTGatewayRequest extends FormRequest
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
            'device_id' => ['required', 'string', 'max:100', 'unique:iot_gateways,device_id'],
            'station_id' => ['nullable', 'string', 'exists:stations,id'],
            'status' => ['required', 'string', 'in:online,offline,maintenance,unpaired'],
        ];
    }
}
