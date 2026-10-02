<?php

declare(strict_types=1);

namespace Modules\IoT\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\System\Models\Machine;
use Modules\System\Models\User;

class IoTMLBurst extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'iot_ml_bursts';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'dataset_id',
        'device_log_id',
        'node_id',
        'machine_id',
        'session_id',
        'origin',
        'rpm',
        'rms_global',
        'windows_count',
        'predicted_label',
        'predicted_confidence',
        'ground_truth_label',
        'is_validated',
        'validated_by_user_id',
        'validated_at',
        'features_summary',
        'burst_storage_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features_summary' => 'array',
            'rpm' => 'integer',
            'rms_global' => 'float',
            'windows_count' => 'integer',
            'predicted_confidence' => 'float',
            'is_validated' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(IoTMLDataset::class, 'dataset_id');
    }

    public function deviceLog(): BelongsTo
    {
        return $this->belongsTo(IoTDeviceLog::class, 'device_log_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(IoTNode::class, 'node_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by_user_id');
    }
}
