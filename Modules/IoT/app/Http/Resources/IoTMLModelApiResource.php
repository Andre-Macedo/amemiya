<?php

declare(strict_types=1);

namespace Modules\IoT\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Serialização de Modelos Preditivos do Model Registry.
 *
 * @property string $id
 * @property string|null $dataset_id
 * @property string $name
 * @property string $model_type
 * @property string $version
 * @property string $target_device
 * @property string $status
 * @property string|null $artifact_path
 * @property array|null $metrics
 * @property string|null $notes
 * @property Carbon|null $deployed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class IoTMLModelApiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dataset_id' => $this->dataset_id,
            'name' => $this->name,
            'model_type' => $this->model_type,
            'version' => $this->version,
            'target_device' => $this->target_device,
            'status' => $this->status,
            'artifact_path' => $this->artifact_path,
            'metrics' => $this->metrics,
            'notes' => $this->notes,
            'deployed_at' => $this->deployed_at?->toIso8601String(),
            'dataset' => $this->whenLoaded('dataset'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
