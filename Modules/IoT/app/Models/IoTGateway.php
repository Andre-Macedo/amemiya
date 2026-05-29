<?php

namespace Modules\IoT\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\System\Models\Station;

class IoTGateway extends Model
{
    use BelongsToTenant, HasUlids, SoftDeletes;

    protected $table = 'iot_gateways';

    protected $fillable = [
        'tenant_id',
        'station_id',
        'name',
        'device_id',
        'status',
    ];

    /**
     * Um Gateway pode estar em uma Bancada (Station).
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Um Gateway gerencia vários Nós de Borda (via ESP-NOW).
     */
    public function nodes(): HasMany
    {
        return $this->hasMany(IoTNode::class, 'gateway_id');
    }
}
