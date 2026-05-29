<?php

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogApiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'event' => $this->event, // created, updated
            'user_name' => $this->user ? $this->user->name : 'System/Unknown',
            'created_at' => $this->created_at->toIso8601String(),
            'formatted_date' => $this->created_at->format('d/m/Y H:i'),
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'url' => $this->url,
        ];
    }
}
