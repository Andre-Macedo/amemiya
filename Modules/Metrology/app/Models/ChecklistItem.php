<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Metrology\Database\Factories\ChecklistItemFactory;

/**
 * @property string $id
 * @property int $checklist_id
 * @property string $step
 * @property string $question_type
 * @property int $order
 * @property int $required_readings
 * @property bool $completed
 * @property array|null $as_found_readings
 * @property array|null $as_left_readings
 * @property bool $adjusted
 * @property string|null $uncertainty
 * @property string|null $result
 * @property string|null $notes
 * @property int|null $reference_standard_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ChecklistItem extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'checklist_id',
        'step',
        'nominal_value',
        'question_type',
        'order',
        'required_readings',
        'completed',
        'as_found_readings',
        'as_left_readings',
        'adjusted',
        'uncertainty',
        'result',
        'notes',
        'reference_standard_id',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'adjusted' => 'boolean',
        'as_found_readings' => 'array',
        'as_left_readings' => 'array',
        'question_type' => 'string',
        'nominal_value' => 'decimal:6',
    ];

    /**
     * Accessor de compatibilidade: retorna as_left_readings se houver ajuste, senão as_found_readings.
     */
    public function getReadingsAttribute(): ?array
    {
        return $this->as_left_readings ?: $this->as_found_readings;
    }

    /**
     * Mutator de compatibilidade: se passar 'readings', salva em as_found_readings.
     */
    public function setReadingsAttribute(mixed $value): void
    {
        $this->attributes['as_found_readings'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * @return BelongsTo<Checklist, ChecklistItem>
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /**
     * Padrão de referência utilizado neste item (se aplicável).
     *
     * @return BelongsTo<ReferenceStandard, ChecklistItem>
     */
    public function referenceStandard(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandard::class);
    }

    public static function factory(): ChecklistItemFactory
    {
        return ChecklistItemFactory::new();
    }
}
