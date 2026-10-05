<?php

namespace Modules\IoT\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\IoT\Models\IoTSensorData;

class IoTHistoryApiController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'node_id' => 'nullable|exists:iot_nodes,id',
            'period' => 'nullable|string|in:1h,6h,24h,7d',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'limit' => 'nullable|integer|max:10000',
        ]);

        $query = IoTSensorData::query();

        if ($request->filled('node_id')) {
            $query->where('node_id', $request->node_id);
        }

        if ($request->filled('period')) {
            $hours = match ($request->period) {
                '1h' => 1,
                '6h' => 6,
                '7d' => 168,
                default => 24,
            };
            $query->where('measured_at', '>=', now()->subHours($hours));
        }

        if ($request->filled('start_date')) {
            $query->where('measured_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('measured_at', '<=', $request->end_date);
        }

        $history = $query->latest('measured_at')
            ->limit((int) $request->get('limit', 500))
            ->get()
            ->reverse()
            ->values();

        $mapped = $history->map(function (IoTSensorData $record) {
            $features = is_array($record->features) ? $record->features : [];
            $confidence = (float) ($record->ml_confidence ?? 0.99);
            $isAnomaly = $record->ml_status === 'anomaly';

            return array_merge($record->toArray(), [
                'timestamp' => $record->measured_at?->toIso8601String() ?? $record->created_at?->toIso8601String(),
                'temperature' => isset($features['temperature']) ? (float) $features['temperature'] : 20.0,
                'humidity' => isset($features['humidity']) ? (float) $features['humidity'] : 50.0,
                'vibration_rms' => (float) ($record->rms_global ?? 0.0),
                'accel_x' => (float) ($record->rms_x ?? 0.0),
                'accel_y' => (float) ($record->rms_y ?? 0.0),
                'accel_z' => (float) ($record->rms_z ?? 0.0),
                'ml_anomaly_score' => $isAnomaly ? round(1 - $confidence, 4) : 0.02,
            ]);
        });

        return response()->json(['data' => $mapped]);
    }
}
