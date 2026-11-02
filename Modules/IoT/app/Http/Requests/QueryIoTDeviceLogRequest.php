<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação de parâmetros de listagem e filtragem de logs operacionais IoT.
 */
class QueryIoTDeviceLogRequest extends FormRequest
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
            'node_id' => ['nullable', 'string'],
            'gateway_id' => ['nullable', 'string'],
            'level' => ['nullable', 'string', 'in:info,warning,error,critical'],
            'iso_zone' => ['nullable', 'string', 'in:A,B,C,D'],
            'event_type' => ['nullable', 'string'],
            'only_anomalies' => ['nullable', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ];
    }
}
