<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Modules\Metrology\Database\Factories\CalibrationFactory;
use Modules\Metrology\Database\Factories\ReferenceStandardFactory;

class ReferenceStandard extends Model
{
    protected $fillable = [
        'name',
        'parent_id',
        'serial_number',
        'stock_number',
        'reference_standard_type_id',
        'description',
        'calibration_due',
        'status',

        'nominal_value',
        'unit',
        'actual_value',
        'uncertainty',
        'grade',
    ];

    protected $casts = [
        'nominal_value' => 'decimal:6',
        'actual_value' => 'decimal:6',
        'uncertainty' => 'decimal:6',
        'calibration_due' => 'date',
    ];

    protected $appends = [
        'effective_serial_number',
        'effective_stock_number',
    ];


    public function referenceStandardType(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandardType::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandard::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ReferenceStandard::class, 'parent_id');
    }

    public function calibrations(): MorphMany
    {
        return $this->morphMany(Calibration::class, 'calibrated_item');
    }
    public static function factory(): ReferenceStandardFactory
    {
        return ReferenceStandardFactory::new();
    }

    public function latestCalibration(): MorphOne
    {
        return $this->morphOne(Calibration::class, 'calibrated_item')->latestOfMany();
    }

    public function getActiveCertificateUrlAttribute(): ?string
    {
        // 1. Tenta calibração própria
        if ($this->latestCalibration && $this->latestCalibration->certificate_path) {
            return $this->latestCalibration->certificate_path;
        }

        // 2. Se não tem, e for filho, tenta do Pai
        if ($this->parent_id && $this->parent->latestCalibration) {
            return $this->parent->latestCalibration->certificate_path;
        }

        return null;
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

    public function getEffectiveSerialNumberAttribute(): string
    {
        if ($this->serial_number) {
            return $this->serial_number;
        }

        if ($this->parent_id && $this->parent) {
            return $this->parent->serial_number . ' (Kit)';
        }

        return 'S/N';
    }

    public function getEffectiveStockNumberAttribute(): string
    {
        if (!empty($this->stock_number)) {
            return $this->stock_number;
        }

        if ($this->parent_id && $this->parent) {
            // Retorna o do Pai indicando vínculo
            return $this->parent->stock_number . ' (Kit)';
        }

        return 'N/A';
    }
}
