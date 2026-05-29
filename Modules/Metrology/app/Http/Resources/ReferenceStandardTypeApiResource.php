<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes ReferenceStandardType models.
 */
class ReferenceStandardTypeApiResource extends JsonResource
{
    /**
     * Transforms the resource into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the reference standard type.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
