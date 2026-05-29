<?php

declare(strict_types=1);

namespace Modules\System\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes User models for the API.
 */
class UserApiResource extends JsonResource
{
    /**
     * Transforms the user into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the user.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->roles->first()?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
