<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Metrology\Database\Factories\CalibrationFactory;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Events\CalibrationSaved;
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

    public ?array $checklistInput = null;

    public ?array $kitItemsInput = null;

    public ?float $nominal_value = null;

    public ?float $actual_value = null;

    public function setNominalValueAttribute($value): void
    {
        $this->nominal_value = $value !== null ? (float) $value : null;
        $this->calculateDeviationFromValues();
    }

    public function getNominalValueAttribute(): ?float
    {
        return $this->nominal_value;
    }

    public function setActualValueAttribute($value): void
    {
        $this->actual_value = $value !== null ? (float) $value : null;
        $this->calculateDeviationFromValues();
    }

    public function getActualValueAttribute(): ?float
    {
        return $this->actual_value;
    }

    protected function calculateDeviationFromValues(): void
    {
        if ($this->nominal_value !== null && $this->actual_value !== null) {
            $this->attributes['deviation'] = round($this->actual_value - $this->nominal_value, 8);
        }
    }

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
        'certificate_code',
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

    protected $dispatchesEvents = [
        'saved' => CalibrationSaved::class,
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

    public function isRectification(): bool
    {
        return ! empty($this->replaces_calibration_id);
    }

    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_calibration_id');
    }

    public function rectifications(): HasMany
    {
        return $this->hasMany(self::class, 'replaces_calibration_id');
    }

    public function getCertificateCodeAttribute(): ?string
    {
        return $this->attributes['certificate_code'] ?? ($this->id ? 'CERT-'.substr((string) $this->id, -8) : null);
    }

    public function getCertificateNumberAttribute(): ?string
    {
        return $this->attributes['certificate_code'] ?? ($this->id ? 'CERT-'.substr((string) $this->id, -8) : null);
    }

    protected static function factory(): CalibrationFactory
    {
        return CalibrationFactory::new();
    }
}
