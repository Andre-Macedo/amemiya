<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\System\Models\User;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $instrument_id
 * @property ?string $reference_standard_id
 * @property ?Carbon $check_date
 * @property string $result
 * @property ?float $nominal_value
 * @property ?float $measured_value
 * @property ?float $deviation
 * @property ?string $performed_by
 * @property ?float $temperature
 * @property ?float $humidity
 * @property ?string $notes
 * @property ?Instrument $instrument
 * @property ?ReferenceStandard $referenceStandard
 * @property ?User $performer
 */
class IntermediateCheck extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'instrument_id',
        'reference_standard_id',
        'check_date',
        'result', // passed, failed
        'nominal_value',
        'measured_value',
        'deviation',
        'performed_by',
        'temperature',
        'humidity',
        'notes',
    ];

    protected $casts = [
        'check_date' => 'date',
        'nominal_value' => 'decimal:5',
        'measured_value' => 'decimal:5',
        'deviation' => 'decimal:5',
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (IntermediateCheck $check): void {
            if ($check->nominal_value !== null && $check->measured_value !== null && $check->deviation === null) {
                $check->deviation = round((float) $check->measured_value - (float) $check->nominal_value, 5);
            }
        });
    }

    /**
     * @return BelongsTo<Instrument, $this>
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    /**
     * @return BelongsTo<ReferenceStandard, $this>
     */
    public function referenceStandard(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandard::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
