<?php

declare(strict_types=1);

namespace Modules\System\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes Supplier models.
 */
class SupplierApiResource extends JsonResource
{
    /**
     * Transforms the supplier into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the supplier.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cnpj' => $this->cnpj,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'is_manufacturer' => $this->is_manufacturer,
            'is_calibration_provider' => $this->is_calibration_provider,
            'is_maintenance_provider' => $this->is_maintenance_provider,
        ];
    }
}
