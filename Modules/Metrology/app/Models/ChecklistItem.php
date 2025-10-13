<?php

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

 use Modules\Metrology\Database\Factories\ChecklistItemFactory;

class ChecklistItem extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'checklist_id',
        'step',
        'question_type',
        'order',
        'required_readings',
        'completed',
        'readings',
        'uncertainty',
        'result',
        'notes',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'readings' => 'array',
        'question_type' => 'string',
    ];

    public function checklist(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

     public static function factory(): ChecklistItemFactory
     {
          return ChecklistItemFactory::new();
     }
}
