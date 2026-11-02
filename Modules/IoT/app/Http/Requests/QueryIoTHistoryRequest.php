<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação dos parâmetros de consulta de histórico de telemetria.
 */
class QueryIoTHistoryRequest extends FormRequest
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
            'node_id' => ['nullable', 'string', 'exists:iot_nodes,id'],
            'period' => ['nullable', 'string', 'in:1h,6h,24h,7d'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ];
    }
}
