<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Database\Factories\InstrumentFactory;

// use Modules\Metrology\Database\Factories\InstrumentFactory;

class Instrument extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'serial_number',
        'type',
        'precision',
        'location',
        'acquisition_date',
        'calibration_due',
        'status',
        'nfc_tag',
        'current_station_id'
    ];

    public function calibrations(): HasMany
    {
        return $this->hasMany(Calibration::class);
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, "current_station_id");
    }

    protected static function factory(): InstrumentFactory
    {
        return InstrumentFactory::new();
    }
}
