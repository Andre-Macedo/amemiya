<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Database\Factories\InstrumentTypeFactory;

/**
 * @property int $id
 * @property string $name
 * @property int|null $calibration_frequency_months
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class InstrumentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'calibration_frequency_months', 'decision_rule'];

    protected $casts = [
        'decision_rule' => 'string', // or enum if using PHP 8.1+ Enum
    ];

    public function instruments(): HasMany
    {
        return $this->hasMany(Instrument::class);
    }

    public function checklistTemplates(): HasMany
    {
        return $this->hasMany(ChecklistTemplate::class);
    }

    public static function factory(): InstrumentTypeFactory {
        return InstrumentTypeFactory::new();
    }
}
