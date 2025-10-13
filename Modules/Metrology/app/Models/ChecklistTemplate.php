<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

 use Modules\Metrology\Database\Factories\ChecklistTemplateFactory;

class ChecklistTemplate extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['name', 'instrument_type'];

    public function checklists(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Checklist::class);
    }

    public function items(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ChecklistTemplateItem::class);
    }

     public static function factory(): ChecklistTemplateFactory
     {
          return ChecklistTemplateFactory::new();
     }
}
