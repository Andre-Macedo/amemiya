<?php

declare(strict_types=1);

namespace Modules\System\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes Station models.
 */
class StationApiResource extends JsonResource
{
    /**
     * Transforms the station into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the station.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->metadata['code'] ?? null,
            'location' => $this->location,
            'type' => $this->type,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'instruments_count' => $this->instruments_count ?? $this->instruments()->count(),
            'sensors_count' => $this->nodes()->count(),
            'instruments' => $this->relationLoaded('instruments') ? $this->instruments : null,
            'nodes' => $this->relationLoaded('nodes') ? $this->nodes : null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
