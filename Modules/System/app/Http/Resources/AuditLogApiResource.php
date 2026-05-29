<?php

declare(strict_types=1);

namespace Modules\System\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Serializes AuditLog models.
 */
class AuditLogApiResource extends JsonResource
{
    /**
     * Transforms the audit log into an array.
     *
     * Args:
     *     request: The incoming HTTP request.
     *
     * Returns:
     *     A serialized representation of the audit log.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'event' => $this->event,
            'user_name' => $this->user ? $this->user->name : 'System/Unknown',
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'url' => $this->url,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->toIso8601String(),
            'formatted_date' => $this->created_at?->format('d/m/Y H:i'),
        ];
    }
}
