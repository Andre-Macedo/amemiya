<?php

namespace Modules\IoT\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\System\Models\Machine;

class IoTNode extends Model
{
    use BelongsToTenant, HasUlids, SoftDeletes;

    protected $table = 'iot_nodes';

    protected $fillable = [
        'tenant_id',
        'gateway_id',
        'machine_id',
        'name',
        'node_id',
        'status',
    ];

    /**
     * Um Nó pertence a um Gateway.
     */
    public function gateway(): BelongsTo
    {
        return $this->belongsTo(IoTGateway::class, 'gateway_id');
    }

    /**
     * Um Nó está fixado em uma Máquina (do módulo System).
     */
    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class, 'machine_id');
    }

    /**
     * Um Nó tem muitos registros de telemetria.
     */
    public function telemetryData(): HasMany
    {
        return $this->hasMany(IoTSensorData::class, 'node_id');
    }
}
