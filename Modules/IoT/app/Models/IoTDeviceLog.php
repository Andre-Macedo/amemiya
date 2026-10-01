<?php

declare(strict_types=1);

namespace Modules\IoT\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\System\Models\Machine;

class IoTDeviceLog extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'iot_device_logs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'gateway_id',
        'node_id',
        'machine_id',
        'level',
        'event_type',
        'ml_status',
        'ml_confidence',
        'cloud_ml_status',
        'cloud_ml_confidence',
        'rpm',
        'rms_global',
        'raw_payload',
        'features',
        'sent_command',
        'message',
        'measured_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'features' => 'array',
            'sent_command' => 'array',
            'ml_confidence' => 'float',
            'cloud_ml_confidence' => 'float',
            'rms_global' => 'float',
            'rpm' => 'integer',
            'measured_at' => 'datetime',
        ];
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(IoTGateway::class, 'gateway_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(IoTNode::class, 'node_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }
}
