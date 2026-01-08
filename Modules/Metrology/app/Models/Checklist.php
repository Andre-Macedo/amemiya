<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

 use Modules\Metrology\Database\Factories\ChecklistFactory;

/**
 * @property int $id
 * @property int $calibration_id
 * @property int $checklist_template_id
 * @property array|null $steps
 * @property bool $completed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Checklist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'calibration_id',
        'checklist_template_id',
        'steps',
        'completed'
        ];

    protected $casts = [
        'steps' => 'array',
        'completed' => 'boolean',
    ];

    public function calibration()
    {
        return $this->belongsTo(Calibration::class);
    }

    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

     public static function factory(): ChecklistFactory
     {
          return ChecklistFactory::new();
     }
}
