<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Metrology\Database\Factories\ChecklistFactory;

/**
 * @property string $id
 * @property int $tenant_id
 * @property int $calibration_id
 * @property int $checklist_template_id
 * @property array|null $steps
 * @property bool $completed
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Checklist extends Model
{
    use BelongsToTenant, HasUlids;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'tenant_id',
        'calibration_id',
        'checklist_template_id',
        'steps',
        'completed',
    ];

    protected $casts = [
        'steps' => 'array',
        'completed' => 'boolean',
    ];

    public function calibration(): BelongsTo
    {
        return $this->belongsTo(Calibration::class);
    }

    /**
     * @return BelongsTo<ChecklistTemplate, Checklist>
     */
    public function checklistTemplate(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class);
    }

    /**
     * @return HasMany<ChecklistItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class);
    }

    public static function factory(): ChecklistFactory
    {
        return ChecklistFactory::new();
    }
}
