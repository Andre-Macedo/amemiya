<?php

declare(strict_types=1);

namespace Modules\IoT\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IoTMLModel extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'iot_ml_models';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'dataset_id',
        'name',
        'model_type',
        'version',
        'target_device',
        'status',
        'artifact_path',
        'metrics',
        'deployed_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'deployed_at' => 'datetime',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(IoTMLDataset::class, 'dataset_id');
    }
}
