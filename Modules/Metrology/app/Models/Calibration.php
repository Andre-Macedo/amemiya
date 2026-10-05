<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
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
 * @property ?string $tenant_id
 * @property string $verification_hash
 * @property ?string $certificate_code
 * @property ?string $pdf_hash
 * @property ?string $certificate_path
 * @property CalibrationResult $result
 * @property ?Carbon $calibration_date
 * @property ?Carbon $next_due_date
 * @property ?float $deviation
 * @property ?float $as_found_deviation
 * @property ?float $as_left_deviation
 * @property ?float $uncertainty
 * @property ?float $temperature
 * @property ?float $humidity
 * @property ?string $notes
 * @property ?string $conformity_statement
 * @property ?string $performed_by_id
 * @property ?string $approved_by_id
 * @property ?string $provider_id
 * @property ?Carbon $approved_at
 * @property ?string $status
 * @property ?string $replaces_calibration_id
 * @property ?string $amendment_reason
 * @property ?User $performedBy
 * @property ?User $approvedBy
 * @property ?Supplier $provider
 * @property ?Checklist $checklist
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
        'pdf_hash',
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
        'lab_client_id',
        'as_received_condition',
        'received_date',
        'show_calibration_due',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'received_date' => 'date',
        'show_calibration_due' => 'boolean',
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

            if (empty($model->certificate_code)) {
                $year = $model->calibration_date ? $model->calibration_date->format('Y') : now()->format('Y');
                $model->certificate_code = 'CAL-'.$year.'-'.strtoupper(substr((string) Str::ulid(), -6));
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

    /**
     * @return BelongsTo<User, $this>
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'provider_id');
    }

    public function labClient(): BelongsTo
    {
        return $this->belongsTo(LabClient::class, 'lab_client_id');
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
