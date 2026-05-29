<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Metrology\Database\Factories\InstrumentTypeFactory;
use Modules\System\Models\Supplier;

/**
 * @property string $id
 * @property string $name
 * @property int|null $calibration_frequency_months
 * @property string $decision_rule
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class InstrumentType extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'calibration_frequency_months',
        'decision_rule',
        'guard_band_multiplier',
        'pass_statement_template',
        'fail_statement_template',
    ];

    protected $casts = [
        'decision_rule' => 'string',
    ];

    /**
     * @return HasMany<Instrument>
     */
    public function instruments(): HasMany
    {
        return $this->hasMany(Instrument::class);
    }

    /**
     * @return HasMany<ChecklistTemplate>
     */
    public function checklistTemplates(): HasMany
    {
        return $this->hasMany(ChecklistTemplate::class);
    }

    /**
     * @return BelongsToMany<Supplier, $this>
     */
    public function accreditedSuppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_accreditations')
            ->withPivot(['range', 'uncertainty'])
            ->withTimestamps();
    }

    public static function factory(): InstrumentTypeFactory
    {
        return InstrumentTypeFactory::new();
    }
}
