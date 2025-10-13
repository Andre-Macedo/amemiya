<?php

namespace Modules\Metrology\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Metrology\Database\Factories\CalibrationFactory;
use Modules\Metrology\Database\Factories\InstrumentFactory;

class Calibration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'instrument_id',
        'checklist_id',
        'calibration_interval',
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

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Accessor for next_calibration_due (computed from interval)
    public function getNextCalibrationDueAttribute(): ?Carbon
    {
        if ($this->calibration_interval && $this->calibration_date) {
            return $this->calibration_date->addMonths($this->calibration_interval);
        }
        return null;
    }

}
