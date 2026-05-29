<?php

declare(strict_types=1);

namespace Modules\System\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes AccessLog models.
 */
class AccessLogApiResource extends JsonResource
{
    /**
     * Transforms the access log into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the access log.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'station_id' => $this->station_id,
            'station_name' => $this->station?->name,
            'instrument_id' => $this->instrument_id,
            'instrument_name' => $this->instrument?->name,
            'action' => $this->action,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
