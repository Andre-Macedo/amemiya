<?php

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\System\Models\Supplier;
use Modules\System\Models\User;

class MaintenanceRecord extends Model
{
    use BelongsToTenant, HasUlids, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'instrument_id',
        'type',
        'date',
        'description',
        'findings',
        'parts_replaced',
        'cost',
        'technician_id',
        'supplier_id',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'parts_replaced' => 'array',
        'cost' => 'decimal:2',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
