<?php

namespace Modules\IoT\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IoTSensorData extends Model
{
    use BelongsToTenant, HasUlids;

    protected $table = 'iot_sensor_data';

    protected $fillable = [
        'tenant_id',
        'node_id',
        'msg_id',
        'rpm',
        'rms_global',
        'rms_x',
        'rms_y',
        'rms_z',
        'kurt_x',
        'kurt_y',
        'kurt_z',
        'mic_rms',
        'features',
        'fft_data',
        'ml_status',
        'ml_confidence',
        'cloud_ml_status',
        'cloud_ml_confidence',
        'measured_at',
    ];

    protected $casts = [
        'fft_data' => 'array',
        'features' => 'array',
        'measured_at' => 'datetime',
        'rms_global' => 'float',
        'rms_x' => 'float',
        'rms_y' => 'float',
        'rms_z' => 'float',
        'kurt_x' => 'float',
        'kurt_y' => 'float',
        'kurt_z' => 'float',
        'mic_rms' => 'float',
        'ml_confidence' => 'float',
        'cloud_ml_confidence' => 'float',
    ];

    /**
     * O dado de telemetria pertence a um Nó de Borda.
     */
    public function node(): BelongsTo
    {
        return $this->belongsTo(IoTNode::class, 'node_id');
    }
}
