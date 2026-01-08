<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Metrology\Database\Factories\ReferenceStandardTypeFactory;

// use Modules\Metrology\Database\Factories\ReferenceStandardTypeFactory;

/**
 * @property int $id
 * @property string $name
 * @property int|null $calibration_frequency_months
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ReferenceStandardType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'calibration_frequency_months'
    ];

    public function referenceStandards(): HasMany
    {
        return $this->hasMany(ReferenceStandard::class);
    }

    public function checklistTemplateItems(): HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class);
    }

    protected static function factory(): ReferenceStandardTypeFactory
    {
        return ReferenceStandardTypeFactory::new();
    }
}
