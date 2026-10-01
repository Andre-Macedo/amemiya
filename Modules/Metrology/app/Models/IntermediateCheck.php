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
        'performed_by',
        'temperature',
        'humidity',
        'notes',
    ];

    protected $casts = [
        'check_date' => 'date',
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
    ];

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
