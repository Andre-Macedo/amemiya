<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes Material models for thermal correction calculations.
 */
class MaterialApiResource extends JsonResource
{
    /**
     * Transforms the resource into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the material.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cte' => (float) $this->cte, // Coefficient of Thermal Expansion
            'category' => $this->category,
        ];
    }
}
