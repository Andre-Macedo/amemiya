<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Metrology\Database\Factories\CalibrationFactory;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\System\Models\Supplier;
use Modules\System\Models\User;

/**
 * @property string $id
 * @property string $verification_hash
 * @property CalibrationResult $result
 */
class Calibration extends Model
{
    use BelongsToTenant, HasUlids, SoftDeletes;

    protected $fillable = [
        'verification_hash',
        'calibrated_item_id',
        'calibrated_item_type',
        'date',
        'calibration_date',
        'technician',
        'result',
        'next_due_date',
        'deviation',
        'as_found_deviation',
        'as_left_deviation',
        'uncertainty',
        'temperature',
        'humidity',
        'notes',
        'conformity_statement',
        'certificate_path',
        'performed_by_id',
        'provider_id',
        'approved_by_id',
        'approved_at',
        'status',
        'replaces_calibration_id',
        'amendment_reason',
        'calculation_data',
        'procedure_snapshot',
        'tenant_id',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'result' => CalibrationResult::class,
        'approved_at' => 'datetime',
        'calculation_data' => 'array',
        'procedure_snapshot' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->verification_hash)) {
                $model->verification_hash = Str::random(32);
            }
        });
    }

    public function calibratedItem(): MorphTo
    {
        return $this->morphTo();
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(Checklist::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'provider_id');
    }

    protected static function factory(): CalibrationFactory
    {
        return CalibrationFactory::new();
    }
}
