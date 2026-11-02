<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serialização padronizada de Nós Sensores IoT (Carrier Board).
 *
 * @property string $id
 * @property string $gateway_id
 * @property string $machine_id
 * @property string $name
 * @property string $node_id
 * @property string $status
 * @property array|null $last_payload
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class IoTNodeApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway_id' => $this->gateway_id,
            'machine_id' => $this->machine_id,
            'name' => $this->name,
            'node_id' => $this->node_id,
            'status' => $this->status,
            'last_payload' => $this->last_payload,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'gateway' => $this->whenLoaded('gateway'),
            'machine' => $this->whenLoaded('machine'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
