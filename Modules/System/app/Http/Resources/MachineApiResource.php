<?php

declare(strict_types=1);

namespace Modules\System\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Serialização padronizada de máquinas industriais monitoradas.
 *
 * @property string $id
 * @property string|null $station_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $status
 * @property array|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MachineApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->station_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'iso_group' => method_exists($this->resource, 'getIsoGroup') ? $this->getIsoGroup() : 'group_2',
            'foundation_type' => method_exists($this->resource, 'getFoundationType') ? $this->getFoundationType() : 'rigid',
            'station' => $this->whenLoaded('station'),
            'nodes' => $this->whenLoaded('nodes'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
