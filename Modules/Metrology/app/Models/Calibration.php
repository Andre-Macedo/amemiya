<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Database\Factories\CalibrationFactory;
use Modules\Metrology\Database\Factories\InstrumentFactory;

class Calibration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'instrument_id',
        'calibration_date',
        'type',
        'result',
        'deviation',
        'uncertainty',
        'notes',
        'certificate_path',
        'performed_by',
    ];

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(Instrument::class);
    }

    public function referenceStandards(): BelongsToMany
    {
        return $this->belongsToMany(ReferenceStandard::class, 'calibration_reference_standard');
    }

    protected static function factory(): CalibrationFactory
    {
        return CalibrationFactory::new();
    }
}
