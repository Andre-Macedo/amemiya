<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Metrology\Database\Factories\CalibrationFactory;
use Modules\Metrology\Database\Factories\ReferenceStandardFactory;

class ReferenceStandard extends Model
{
    protected $fillable = [
        'name',
        'serial_number',
        'type',
        'calibration_date',
        'calibration_due',
        'traceability',
        'certificate_path',
        'description',
    ];

    public function calibrations(): BelongsToMany
    {
        return $this->belongsToMany(Calibration::class, 'calibration_reference_standard');
    }

    public static function factory(): ReferenceStandardFactory
    {
        return ReferenceStandardFactory::new();
    }
}
