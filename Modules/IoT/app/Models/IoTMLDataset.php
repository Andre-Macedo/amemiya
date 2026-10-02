<?php

declare(strict_types=1);

namespace Modules\IoT\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\System\Models\Machine;

class IoTMLDataset extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'iot_ml_datasets';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'target_machine_id',
        'description',
        'status',
        'class_distribution',
        'total_samples',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'class_distribution' => 'array',
            'total_samples' => 'integer',
        ];
    }

    public function targetMachine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'target_machine_id');
    }

    public function bursts(): HasMany
    {
        return $this->hasMany(IoTMLBurst::class, 'dataset_id');
    }

    public function models(): HasMany
    {
        return $this->hasMany(IoTMLModel::class, 'dataset_id');
    }

    /**
     * Recalcula a distribuição de classes e o total de amostras do dataset.
     */
    public function recalculateMetrics(): void
    {
        $distribution = $this->bursts()
            ->whereNotNull('ground_truth_label')
            ->selectRaw('ground_truth_label, count(*) as count')
            ->groupBy('ground_truth_label')
            ->pluck('count', 'ground_truth_label')
            ->toArray();

        $total = (int) $this->bursts()->count();

        $this->updateQuietly([
            'class_distribution' => $distribution,
            'total_samples' => $total,
        ]);
    }
}
