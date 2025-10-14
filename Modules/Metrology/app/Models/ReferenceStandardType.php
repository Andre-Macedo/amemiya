<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Metrology\Database\Factories\ReferenceStandardTypeFactory;

// use Modules\Metrology\Database\Factories\ReferenceStandardTypeFactory;

class ReferenceStandardType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name'];

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
