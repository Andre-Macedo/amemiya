<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NonConformity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'instrument_id',
        'calibration_id',
        'user_id',
        'status',
        'priority',
        'title',
        'description',
        'root_cause_analysis',
        'immediate_action',
        'corrective_action',
        'preventive_action',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function calibration(): BelongsTo
    {
        return $this->belongsTo(Calibration::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
