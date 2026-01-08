<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Metrology\Database\Factories\ChecklistTemplateItemFactory;

// use Modules\Metrology\Database\Factories\ChecklistTemplateItemFactory;

/**
 * @property int $id
 * @property int $checklist_template_id
 * @property string $step
 * @property string $question_type
 * @property int $order
 * @property int $required_readings
 * @property int|null $reference_standard_type_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
        'reference_standard_type_id',
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


    protected static function newFactory(): ChecklistTemplateItemFactory
    {
        return ChecklistTemplateItemFactory::new();
    }
}
