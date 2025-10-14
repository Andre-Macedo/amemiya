<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// use Modules\Metrology\Database\Factories\ChecklistTemplateItemFactory;

class ChecklistTemplateItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'checklist_template_id',
        'step',
        'question_type',
        'order',
        'required_readings',
        'reference_standard_type',
    ];

    protected $casts = [
        'question_type' => 'string',
    ];

    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function referenceStandardType(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandardType::class);
    }


    // protected static function newFactory(): ChecklistTemplateItemFactory
    // {
    //     // return ChecklistTemplateItemFactory::new();
    // }
}
