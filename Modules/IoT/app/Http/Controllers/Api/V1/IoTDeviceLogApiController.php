<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\IoT\Models\IoTDeviceLog;

class IoTDeviceLogApiController extends Controller
{
    /**
     * Lista logs operacionais e de anomalias com filtros avançados.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'node_id' => 'nullable|string',
            'gateway_id' => 'nullable|string',
            'level' => 'nullable|string|in:info,warning,error,critical',
            'iso_zone' => 'nullable|string|in:A,B,C,D',
            'event_type' => 'nullable|string',
            'only_anomalies' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:100',
        ]);

        $query = IoTDeviceLog::query()
            ->with([
                'node:id,name,node_id',
                'gateway:id,name,device_id',
                'machine:id,name',
            ]);

        if ($request->filled('node_id')) {
            $query->where('node_id', $request->input('node_id'));
        }

        if ($request->filled('gateway_id')) {
            $query->where('gateway_id', $request->input('gateway_id'));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        if ($request->filled('iso_zone')) {
            $query->where('iso_zone', $request->input('iso_zone'));
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->boolean('only_anomalies')) {
            $query->where(function ($q) {
                $q->whereIn('level', ['warning', 'critical'])
                    ->orWhereIn('event_type', ['anomaly_detected', 'cloud_ml_evaluated'])
                    ->orWhereIn('iso_zone', ['C', 'D'])
                    ->orWhere('ml_status', 'desbalanceamento')
                    ->orWhere('cloud_ml_status', 'falha_confirmada');
            });
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('ml_status', 'like', "%{$search}%")
                    ->orWhere('cloud_ml_status', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest('created_at')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($logs);
    }

    /**
     * Retorna detalhes completos de um log específico (incluindo payload bruto).
     */
    public function show(string $id): JsonResponse
    {
        $log = IoTDeviceLog::with([
            'node:id,name,node_id',
            'gateway:id,name,device_id',
            'machine:id,name',
        ])->findOrFail($id);

        return response()->json([
            'data' => $log,
        ]);
    }
}
