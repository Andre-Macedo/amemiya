<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Modules\Metrology\Database\Factories\CalibrationFactory;
use Modules\Metrology\Database\Factories\ReferenceStandardFactory;

class ReferenceStandard extends Model
{
    protected $fillable = [
        'name',
        'serial_number',
        'asset_tag',
        'reference_standard_type_id',
        'description',
        ];

    public function calibrations(): MorphMany
    {
        return $this->morphMany(Calibration::class, 'calibrated_item');
    }
    public static function factory(): ReferenceStandardFactory
    {
        return ReferenceStandardFactory::new();
    }
    public function getNextCalibrationDueAttribute(): ?Carbon
    {
        $latestCalibration = $this->calibrations()->latest('calibration_date')->first();

        if ($latestCalibration && $latestCalibration->calibration_interval) {
            return Carbon::parse($latestCalibration->calibration_date)
                ->addMonths($latestCalibration->calibration_interval);
        }

        return null; // Retorna null se não houver histórico ou intervalo
    }
}
