<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\IoT\Http\Requests\QueryIoTDeviceLogRequest;
use Modules\IoT\Http\Resources\IoTDeviceLogApiResource;
use Modules\IoT\Models\IoTDeviceLog;

class IoTDeviceLogApiController extends Controller
{
    /**
     * Lista logs operacionais e de anomalias com filtros avançados.
     */
    public function index(QueryIoTDeviceLogRequest $request): AnonymousResourceCollection
    {
        $query = IoTDeviceLog::query()
            ->with([
                'node:id,name,node_id',
                'gateway:id,name,device_id',
                'machine:id,name',
            ]);

        if ($request->filled('node_id')) {
            $query->where('node_id', $request->validated('node_id'));
        }

        if ($request->filled('gateway_id')) {
            $query->where('gateway_id', $request->validated('gateway_id'));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->validated('level'));
        }

        if ($request->filled('iso_zone')) {
            $query->where('iso_zone', $request->validated('iso_zone'));
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->validated('event_type'));
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
            $query->where('created_at', '>=', $request->validated('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->validated('end_date'));
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

        return IoTDeviceLogApiResource::collection($logs);
    }

    /**
     * Retorna detalhes completos de um log específico (incluindo payload bruto).
     */
    public function show(string $id): IoTDeviceLogApiResource
    {
        $log = IoTDeviceLog::with([
            'node:id,name,node_id',
            'gateway:id,name,device_id',
            'machine:id,name',
        ])->findOrFail($id);

        return new IoTDeviceLogApiResource($log);
    }
}
