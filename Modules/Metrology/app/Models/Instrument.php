<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Models\Station;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Database\Factories\InstrumentFactory;
use Modules\Metrology\Services\DecisionRules\DecisionRuleStrategy;
use Modules\Metrology\Services\DecisionRules\GuardBand;
use Modules\Metrology\Services\DecisionRules\SimpleAcceptance;
use Modules\Metrology\Services\DecisionRules\UncertaintyAccounted;

// use Modules\Metrology\Database\Factories\InstrumentFactory;

/**
 * @property int $id
 * @property string $name
 * @property string|null $stock_number
 * @property string $serial_number
 * @property int $instrument_type_id
 * @property string|null $mpe
 * @property string|null $measuring_range
 * @property string|null $resolution
 * @property string|null $manufacturer
 * @property string|null $location
 * @property \Illuminate\Support\Carbon $acquisition_date
 * @property \Illuminate\Support\Carbon $calibration_due
 * @property string $status
 * @property string|null $nfc_tag
 * @property int|null $current_station_id
 * @property int|null $current_supplier_id
 * @property string|null $image_path
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Instrument extends Model implements CalibratableItem
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'stock_number',
        'serial_number',
        'instrument_type_id',
        'mpe',
        'measuring_range',
        'resolution',
        'manufacturer',
        'location',
        'acquisition_date',
        'calibration_due',
        'status',
        'nfc_tag',
        'current_station_id',
        'current_supplier_id',
        'image_path',
    ];

    protected $casts = [
        'calibration_due' => 'datetime',
        'acquisition_date' => 'datetime',
        'next_calibration_date' => 'datetime',
        'status' => \Modules\Metrology\Enums\ItemStatus::class,
    ];

    public function calibrations(): MorphMany
    {
        return $this->morphMany(Calibration::class, 'calibrated_item');
    }

    protected static function factory(): InstrumentFactory
    {
        return InstrumentFactory::new();
    }

    public function instrumentType(): BelongsTo
    {
        return $this->belongsTo(InstrumentType::class);
    }

    public function manufacturer(): BelongsTo {
        return $this->belongsTo(Supplier::class, 'manufacturer_id');
    }
    public function station(): BelongsTo {
        return $this->belongsTo(Station::class, 'current_station_id');
    }

    public function currentSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'current_supplier_id');
    }

    public function getMpeValue(): float
    {
        if (empty($this->mpe)) {
            return 0.0;
        }

        // Safety check for Relative/Percentage errors which are not supported yet as absolute limits
        if (str_contains((string)$this->mpe, '%')) {
            return 0.0; 
        }

        // Normalize comma to dot
        $normalized = str_replace(',', '.', (string) $this->mpe);
        
        // Remove everything that is NOT a digit or a dot (e.g. "0.05 mm" -> "0.05")
        $numericValue = preg_replace('/[^0-9.]/', '', $normalized);

        // Parse to float, verifying if it's a valid numeric string
        return is_numeric($numericValue) ? (float) $numericValue : 0.0;
    }

    public function getDecisionRule(): string
    {
        // Default to 'simple' if type not found or not set
        return $this->instrumentType?->decision_rule ?? 'simple';
    }

    public function getCalibrationFrequencyMonths(): int
    {
        return $this->instrumentType?->calibration_frequency_months ?? 12; // Default 12 if missing
    }

    public function getMaximumPermissibleError(): ?float
    {
        // If the property itself is null, try to parse it.
        // But getMpeValue() handles parsing logic.
        return $this->getMpeValue();
    }

    public function getDecisionRuleStrategy(): DecisionRuleStrategy
    {
        $rule = $this->getDecisionRule();

        return match ($rule) {
            'guard_band' => new GuardBand(),
            'uncertainty_accounted' => new UncertaintyAccounted(),
            default => new SimpleAcceptance(),
        };
    }

    public function processCalibrationResult(Calibration $calibration, \Modules\Metrology\Enums\CalibrationResult $status): void
    {
        if (in_array($status, [\Modules\Metrology\Enums\CalibrationResult::Approved, \Modules\Metrology\Enums\CalibrationResult::ApprovedWithRestrictions])) {
             // 1. Calculate Due Date
             $months = $this->getCalibrationFrequencyMonths();
             $nextDate = $calibration->calibration_date->copy()->addMonths($months);

             // 2. Update Data
             $this->calibration_due = $nextDate;
             $this->status = \Modules\Metrology\Enums\ItemStatus::Active;
             $this->current_supplier_id = null; // Returned from calibration
             
             $this->save();
             
        } elseif ($status === \Modules\Metrology\Enums\CalibrationResult::Rejected) {
             $this->status = \Modules\Metrology\Enums\ItemStatus::Rejected;
             $this->save();
        }
    }
}
