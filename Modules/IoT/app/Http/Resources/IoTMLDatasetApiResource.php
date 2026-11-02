<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Serialização de Datasets de Treinamento e Benchmarking MLOps.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $type
 * @property string|null $target_machine_id
 * @property string|null $description
 * @property string $status
 * @property array|null $class_distribution
 * @property int $total_samples
 * @property int|null $bursts_count
 * @property int|null $models_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class IoTMLDatasetApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'target_machine_id' => $this->target_machine_id,
            'description' => $this->description,
            'status' => $this->status,
            'class_distribution' => $this->class_distribution,
            'total_samples' => $this->total_samples ?? 0,
            'bursts_count' => $this->bursts_count ?? 0,
            'models_count' => $this->models_count ?? 0,
            'target_machine' => $this->whenLoaded('targetMachine'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
