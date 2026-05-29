<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\System\Models\User;

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
     * @return BelongsTo<Instrument, IntermediateCheck>
     */
    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    /**
     * @return BelongsTo<ReferenceStandard, IntermediateCheck>
     */
    public function referenceStandard(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandard::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
