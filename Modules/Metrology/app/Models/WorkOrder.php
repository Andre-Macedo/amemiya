<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\System\Models\Station;
use Modules\System\Models\User;

class WorkOrder extends Model
{
    use BelongsToTenant, HasUlids, LogsActivity;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'number',
        'item_id',
        'item_type',
        'status',
        'origin_station_id',
        'destination_station_id',
        'courier_name',
        'dispatched_at',
        'visual_inspection_notes',
        'customer_notes',
        'expected_return_date',
        'received_by_id',
    ];

    protected $casts = [
        'expected_return_date' => 'date',
        'dispatched_at' => 'datetime',
    ];

    /**
     * Get the parent item model (Instrument or ReferenceStandard).
     */
    public function item(): MorphTo
    {
        return $this->morphTo();
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function originStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'origin_station_id');
    }

    public function destinationStation(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'destination_station_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->number)) {
                $model->number = 'OS-'.date('Y').'-'.strtoupper(substr((string) Str::ulid(), -6));
            }

            // Captura a localização atual do instrumento como Origem da OS
            if ($model->item_id && $model->item_type === Instrument::class) {
                $instrument = Instrument::find($model->item_id);
                if ($instrument) {
                    $model->origin_station_id = $instrument->current_station_id;
                }
            }
        });
    }
}
