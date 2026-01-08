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
use Modules\Metrology\Database\Factories\InstrumentFactory;

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
class Instrument extends Model
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
        'mpe' => 'decimal:4',
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

        // Normalize comma to dot and remove non-numeric chars except dot
        // Assuming mpe stores just the number or number+unit.
        // Current logic was: preg_replace('/[^0-9.]/', '', str_replace(',', '.', (string)$item->mpe))
        // We will keep similar robustness but encapsulated here.
        
        $normalized = str_replace(',', '.', (string) $this->mpe);
        $numericValue = preg_replace('/[^0-9.]/', '', $normalized);

        return (float) $numericValue;
    }

    public function getDecisionRule(): string
    {
        // Default to 'simple' if type not found or not set
        return $this->instrumentType?->decision_rule ?? 'simple';
    }
}
