<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

 use Modules\Metrology\Database\Factories\ChecklistItemFactory;

/**
 * @property int $id
 * @property int $checklist_id
 * @property string $step
 * @property string $question_type
 * @property int $order
 * @property int $required_readings
 * @property bool $completed
 * @property array|null $readings
 * @property string|null $uncertainty
 * @property string|null $result
 * @property string|null $notes
 * @property int|null $reference_standard_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
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
        'reference_standard_id'
    ];

    protected $casts = [
        'completed' => 'boolean',
        'readings' => 'array',
        'question_type' => 'string',
    ];

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function referenceStandard(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandard::class);
    }

    public static function factory(): ChecklistItemFactory
     {
          return ChecklistItemFactory::new();
     }
}
