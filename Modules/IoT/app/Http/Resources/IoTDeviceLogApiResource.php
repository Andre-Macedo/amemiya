<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialização padronizada de logs de eventos e anomalias IoT.
 *
 * @property string $id
 * @property string $node_id
 * @property string|null $gateway_id
 * @property string|null $machine_id
 * @property string $level
 * @property string $event_type
 * @property string|null $ml_status
 * @property float|null $ml_confidence
 * @property string|null $cloud_ml_status
 * @property float|null $cloud_ml_confidence
 * @property int|null $rpm
 * @property float|null $rms_global
 * @property float|null $velocity_rms
 * @property string|null $iso_zone
 * @property array|null $iso_evaluation
 * @property array|null $raw_payload
 * @property array|null $features
 * @property array|null $sent_command
 * @property string|null $message
 * @property \Illuminate\Support\Carbon|null $measured_at
 * @property \Illuminate\Support\Carbon|null $created_at
 */
class IoTDeviceLogApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'node_id' => $this->node_id,
            'gateway_id' => $this->gateway_id,
            'machine_id' => $this->machine_id,
            'level' => $this->level,
            'event_type' => $this->event_type,
            'ml_status' => $this->ml_status,
            'ml_confidence' => $this->ml_confidence,
            'cloud_ml_status' => $this->cloud_ml_status,
            'cloud_ml_confidence' => $this->cloud_ml_confidence,
            'rpm' => $this->rpm,
            'rms_global' => $this->rms_global,
            'velocity_rms' => $this->velocity_rms,
            'iso_zone' => $this->iso_zone,
            'iso_evaluation' => $this->iso_evaluation,
            'raw_payload' => $this->raw_payload,
            'features' => $this->features,
            'sent_command' => $this->sent_command,
            'message' => $this->message,
            'node' => $this->whenLoaded('node'),
            'gateway' => $this->whenLoaded('gateway'),
            'machine' => $this->whenLoaded('machine'),
            'measured_at' => $this->measured_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
