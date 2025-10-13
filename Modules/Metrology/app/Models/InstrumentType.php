<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Database\Factories\InstrumentTypeFactory;

class InstrumentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

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
