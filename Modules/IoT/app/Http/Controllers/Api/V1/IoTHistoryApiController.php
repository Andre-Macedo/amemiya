<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IoT\Http\Requests\QueryIoTHistoryRequest;
use Modules\IoT\Http\Resources\IoTHistoryApiResource;
use Modules\IoT\Models\IoTSensorData;

class IoTHistoryApiController extends Controller
{
    /**
     * Consulta séries temporais de vibração, temperatura e RPM com filtros de janela.
     */
    public function index(QueryIoTHistoryRequest $request): AnonymousResourceCollection
    {
        $query = IoTSensorData::query();

        if ($request->filled('node_id')) {
            $query->where('node_id', $request->validated('node_id'));
        }

        if ($request->filled('period')) {
            $hours = match ($request->validated('period')) {
                '1h' => 1,
                '6h' => 6,
                '7d' => 168,
                default => 24,
            };
            $query->where('measured_at', '>=', now()->subHours($hours));
        }

        if ($request->filled('start_date')) {
            $query->where('measured_at', '>=', $request->validated('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('measured_at', '<=', $request->validated('end_date'));
        }

        $history = $query->latest('measured_at')
            ->limit((int) $request->input('limit', 500))
            ->get()
            ->reverse()
            ->values();

        return IoTHistoryApiResource::collection($history);
    }
}

