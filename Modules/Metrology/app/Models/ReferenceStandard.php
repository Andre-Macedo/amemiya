<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Database\Factories\CalibrationFactory;
use Modules\Metrology\Database\Factories\ReferenceStandardFactory;
use Modules\Metrology\Services\DecisionRules\DecisionRuleStrategy;
use Modules\Metrology\Services\DecisionRules\SimpleAcceptance;

/**
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property string|null $serial_number
 * @property string|null $stock_number
 * @property int $reference_standard_type_id
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $calibration_due
 * @property string $status
 * @property string|null $nominal_value
 * @property string|null $unit
 * @property string|null $actual_value
 * @property string|null $uncertainty
 * @property string|null $grade
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Modules\Metrology\Models\ReferenceStandard|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Metrology\Models\ReferenceStandard> $children
 * @property-read \Modules\Metrology\Models\Calibration|null $latestCalibration
 */
class ReferenceStandard extends Model implements CalibratableItem
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
        'status' => \Modules\Metrology\Enums\ItemStatus::class,
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
        /** @var Calibration|null $latestCalibration */
        $latestCalibration = $this->latestCalibration;

        if ($latestCalibration) {
            $months = $this->referenceStandardType->calibration_frequency_months ?? 24;
            
            return $latestCalibration->calibration_date->copy()->addMonths($months);
        }

        return null; // Retorna null se não houver histórico
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

    public function getCalibrationFrequencyMonths(): int
    {
        return $this->referenceStandardType->calibration_frequency_months ?? 24;
    }

    public function getMaximumPermissibleError(): ?float
    {
        // Reference Standards usually don't have MPE in the same way instruments do for decision rules
        // unless specified. Returning null skips the MPE evaluation logic in the Action.
        return null; 
    }

    public function getDecisionRuleStrategy(): DecisionRuleStrategy
    {
        // Default strategy if none specified
        return new SimpleAcceptance();
    }

    public function processCalibrationResult(Calibration $calibration, \Modules\Metrology\Enums\CalibrationResult $status): void
    {
        if (in_array($status, [\Modules\Metrology\Enums\CalibrationResult::Approved, \Modules\Metrology\Enums\CalibrationResult::ApprovedWithRestrictions])) {
             // 1. Calculate Due Date
             $months = $this->getCalibrationFrequencyMonths();
             $nextDate = $calibration->calibration_date->copy()->addMonths($months);

             // 2. Prepare Update Data
             $updateData = [
                 'calibration_due' => $nextDate,
                 'status' => \Modules\Metrology\Enums\ItemStatus::Active,
             ];

             // 3. Update Actual Value & Uncertainty
             if ($this->nominal_value && $calibration->deviation !== null) {
                 $updateData['actual_value'] = (float) $this->nominal_value + (float) $calibration->deviation;
             }
             if ($calibration->uncertainty) {
                 $updateData['uncertainty'] = $calibration->uncertainty;
             }

             $this->update($updateData);

             // 4. Cascade to Children (Kits)
             if ($this->children()->exists()) {
                 $this->children()->update([
                     'calibration_due' => $nextDate,
                     'status' => \Modules\Metrology\Enums\ItemStatus::Active,
                 ]);
             }

        } elseif ($status === \Modules\Metrology\Enums\CalibrationResult::Rejected) {
            $this->update(['status' => \Modules\Metrology\Enums\ItemStatus::Rejected]);

            if ($this->children()->exists()) {
                $this->children()->update(['status' => \Modules\Metrology\Enums\ItemStatus::Rejected]);
            }
        }
    }
}
