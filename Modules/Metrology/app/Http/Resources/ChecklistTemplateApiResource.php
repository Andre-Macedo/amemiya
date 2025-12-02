<?php

namespace Modules\Metrology\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChecklistTemplateApiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'items' => $this->items->sortBy('order')->map(function ($item) {
                return [
                    'id' => (string) $item->id,
                    'step' => $item->step, // O texto da pergunta/passo
                    'question_type' => $item->question_type, // 'numeric', 'text', 'boolean'
                    'order' => $item->order,
                    'required_readings' => $item->required_readings ?? 1, // Quantas leituras precisa?
                ];
            })->values(),
        ];
    }
}
