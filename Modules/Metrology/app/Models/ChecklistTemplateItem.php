<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Metrology\Database\Factories\ChecklistTemplateItemFactory;

/**
 * @property string $id
 * @property int $checklist_template_id
 * @property string $step
 * @property string $question_type
 * @property int $order
 * @property int $required_readings
 * @property float|null $nominal_value
 * @property float|null $criteria
 * @property int|null $reference_standard_type_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChecklistTemplateItem extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'checklist_template_id',
        'step',
        'question_type',
        'order',
        'required_readings',
        'reference_standard_type_id',
        'nominal_value',
        'criteria',
    ];

    protected $casts = [
        'question_type' => 'string',
        'nominal_value' => 'float',
        'criteria' => 'float',
    ];

    /**
     * @return BelongsTo<ChecklistTemplate, ChecklistTemplateItem>
     */
    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    /**
     * Tipo de padrão de referência sugerido para este passo.
     *
     * @return BelongsTo<ReferenceStandardType, ChecklistTemplateItem>
     */
    public function referenceStandardType(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandardType::class);
    }

    protected static function newFactory(): ChecklistTemplateItemFactory
    {
        return ChecklistTemplateItemFactory::new();
    }
}
