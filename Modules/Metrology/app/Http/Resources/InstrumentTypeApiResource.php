<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes InstrumentType models.
 */
class InstrumentTypeApiResource extends JsonResource
{
    /**
     * Transforms the resource into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the instrument type.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'calibration_frequency_months' => $this->calibration_frequency_months,
            'decision_rule' => $this->decision_rule,
        ];
    }
}
