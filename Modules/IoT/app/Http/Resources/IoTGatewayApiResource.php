<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Serialização padronizada de Gateways IoT.
 *
 * @property string $id
 * @property string $name
 * @property string $device_id
 * @property string|null $station_id
 * @property string $status
 * @property Carbon|null $last_seen_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class IoTGatewayApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'device_id' => $this->device_id,
            'station_id' => $this->station_id,
            'status' => $this->status,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'station' => $this->whenLoaded('station'),
            'nodes' => $this->whenLoaded('nodes'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
