<?php

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\IoT\Models\IoTSensorData;
use Illuminate\Http\Resources\Json\JsonResource;

class IoTHistoryApiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'node_id' => 'nullable|exists:iot_nodes,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer|max:1000',
        ]);

        $query = IoTSensorData::query();

        if ($request->has('node_id')) {
            $query->where('node_id', $request->node_id);
        }

        if ($request->has('start_date')) {
            $query->where('measured_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('measured_at', '<=', $request->end_date);
        }

        $history = $query->latest('measured_at')
            ->limit($request->get('limit', 500))
            ->get()
            ->reverse(); // Para o gráfico ficar na ordem cronológica correta (esquerda -> direita)

        return JsonResource::collection($history);
    }
}
