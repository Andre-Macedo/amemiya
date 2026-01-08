<?php

namespace Modules\Metrology\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntermediateCheck extends Model
{
    use HasFactory;

    protected $fillable = [
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

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function referenceStandard(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandard::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
