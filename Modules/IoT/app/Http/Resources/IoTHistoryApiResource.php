<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialização padronizada de séries temporais de vibração e telemetria.
 *
 * @property string $id
 * @property string $node_id
 * @property float|null $rms_global
 * @property float|null $rms_x
 * @property float|null $rms_y
 * @property float|null $rms_z
 * @property int|null $rotation_rpm
 * @property array|null $features
 * @property string|null $ml_status
 * @property float|null $ml_confidence
 * @property \Illuminate\Support\Carbon|null $measured_at
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class IoTHistoryApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $features = is_array($this->features) ? $this->features : [];
        $confidence = (float) ($this->ml_confidence ?? 0.99);
        $isAnomaly = $this->ml_status === 'anomaly';

        $rpm = isset($features['rpm']) ? (float) $features['rpm'] : (isset($this->rotation_rpm) ? (float) $this->rotation_rpm : 1750.0);
        $acousticDb = isset($features['acoustic_db']) ? (float) $features['acoustic_db'] : 72.5;
        $probeTemp = isset($features['probe_temperature']) ? (float) $features['probe_temperature'] : (isset($features['temperature']) ? (float) $features['temperature'] : 24.5);
        $vbatMv = isset($features['vbat_mv']) ? (float) $features['vbat_mv'] : 3700.0;

        return [
            'id' => $this->id,
            'node_id' => $this->node_id,
            'timestamp' => $this->measured_at?->toIso8601String() ?? $this->created_at?->toIso8601String(),
            'probe_temperature' => $probeTemp,
            'temperature' => $probeTemp,
            'rpm_measured' => $rpm,
            'acoustic_db' => $acousticDb,
            'vibration_rms' => (float) ($this->rms_global ?? 0.0),
            'accel_x' => (float) ($this->rms_x ?? 0.0),
            'accel_y' => (float) ($this->rms_y ?? 0.0),
            'accel_z' => (float) ($this->rms_z ?? 0.0),
            'iso_zone' => $features['iso_zone'] ?? 'A',
            'battery_mv' => $vbatMv,
            'ml_status' => $this->ml_status ?? 'normal',
            'ml_confidence' => $confidence,
            'ml_anomaly_score' => $isAnomaly ? round(1 - $confidence, 4) : 0.02,
        ];
    }
}
