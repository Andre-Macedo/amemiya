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
            'location' => $this->location,
            'hostname' => $this->hostname,
            'ip_address' => $this->ip_address ?? null,
            'status' => $this->status,
            'type' => $this->type,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
