<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Metrology\Database\Factories\ReferenceStandardTypeFactory;

// use Modules\Metrology\Database\Factories\ReferenceStandardTypeFactory;

/**
 * @property string $id
 * @property string $name
 * @property int|null $calibration_frequency_months
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ReferenceStandardType extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'calibration_frequency_months',
    ];

    /**
     * @return HasMany<ReferenceStandard>
     */
    public function referenceStandards(): HasMany
    {
        return $this->hasMany(ReferenceStandard::class);
    }

    /**
     * @return HasMany<ChecklistTemplateItem>
     */
    public function checklistTemplateItems(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class);
    }

    protected static function factory(): ReferenceStandardTypeFactory
    {
        return ReferenceStandardTypeFactory::new();
    }
}
