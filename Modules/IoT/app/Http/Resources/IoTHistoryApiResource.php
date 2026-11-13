<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Serialização padronizada de séries temporais de vibração e telemetria.
 *
 * @property string $id
 * @property string $node_id
 * @property int|null $rpm
 * @property float|null $rms_global
 * @property float|null $rms_x
 * @property float|null $rms_y
 * @property float|null $rms_z
 * @property float|null $mic_rms
 * @property float|null $temp_celsius
 * @property int|null $rotation_rpm
 * @property array|null $features
 * @property string|null $ml_status
 * @property float|null $ml_confidence
 * @property string|null $cloud_ml_status
 * @property float|null $cloud_ml_confidence
 * @property Carbon|null $measured_at
 * @property Carbon|null $created_at
 */
class IoTHistoryApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $features = is_array($this->features) ? $this->features : [];
        $effectiveStatus = $this->cloud_ml_status ?? $this->ml_status ?? 'normal';
        $confidence = (float) ($this->cloud_ml_confidence ?? $this->ml_confidence ?? 0.99);
        $isAnomaly = ! in_array($effectiveStatus, ['normal', 'saudavel', 'healthy', 'desligada', 'machine_off'], true);

        $rpm = $this->rpm !== null
            ? (float) $this->rpm
            : (isset($features['rpm']) ? (float) $features['rpm'] : (isset($this->rotation_rpm) ? (float) $this->rotation_rpm : 1750.0));
        $acousticDb = isset($features['acoustic_db']) ? (float) $features['acoustic_db'] : 72.5;
        $probeTemp = $this->temp_celsius !== null
            ? (float) $this->temp_celsius
            : (isset($features['probe_temperature']) ? (float) $features['probe_temperature'] : (isset($features['temperature']) ? (float) $features['temperature'] : 24.5));
        $vbatMv = isset($features['vbat_mv'])
            ? (float) $features['vbat_mv']
            : (isset($features['battery_voltage']) ? (float) $features['battery_voltage'] * 1000.0 : 3700.0);

        $rmsGlobal = (float) ($this->rms_global ?? 0.0);
        $velocityRms = isset($features['velocity_rms']) ? (float) $features['velocity_rms'] : round($rmsGlobal * 2.2, 2);
        $rmsX = (float) ($this->rms_x ?? $features['x_rms'] ?? 0.0);
        $rmsY = (float) ($this->rms_y ?? $features['y_rms'] ?? 0.0);
        $rmsZ = (float) ($this->rms_z ?? $features['z_rms'] ?? 0.0);
        $measuredIso = $this->measured_at?->toIso8601String() ?? $this->created_at?->toIso8601String();

        return [
            'id' => $this->id,
            'node_id' => $this->node_id,
            'timestamp' => $measuredIso,
            'measured_at' => $measuredIso,
            'probe_temperature' => $probeTemp,
            'temperature' => $probeTemp,
            'rpm_measured' => $rpm,
            'rpm' => $rpm,
            'acoustic_db' => $acousticDb,
            'vibration_rms' => $rmsGlobal,
            'rms_global' => $rmsGlobal,
            'velocity_rms' => $velocityRms,
            'accel_x' => $rmsX,
            'accel_y' => $rmsY,
            'accel_z' => $rmsZ,
            'rms_x' => $rmsX,
            'rms_y' => $rmsY,
            'rms_z' => $rmsZ,
            'iso_zone' => $features['iso_zone'] ?? 'A',
            'battery_mv' => $vbatMv,
            'battery_voltage' => round($vbatMv > 20.0 ? $vbatMv / 1000.0 : $vbatMv, 2),
            'ml_status' => $effectiveStatus,
            'ml_confidence' => $confidence,
            'ml_anomaly_score' => $isAnomaly ? round(1 - $confidence, 4) : 0.02,
            'root_cause' => $features['root_cause'] ?? null,
            'confirmation_source' => $features['confirmation_source'] ?? null,
        ];
    }
}
