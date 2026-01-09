<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Database\Factories\CalibrationFactory;

/**
 * @property int $id
 * @property int|null $calibrated_item_id
 * @property string|null $calibrated_item_type
 * @property int|null $checklist_id
 * @property \Illuminate\Support\Carbon $calibration_date
 * @property string $type
 * @property string $result
 * @property string|null $k_factor
 * @property string|null $deviation
 * @property string|null $uncertainty
 * @property string|null $temperature
 * @property string|null $humidity
 * @property string|null $notes
 * @property string|null $certificate_path
 * @property int|null $performed_by_id
 * @property int|null $provider_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Model|null $calibratedItem
 */
class Calibration extends Model
{
    use SoftDeletes;
    
    // Transient properties for event processing
    public ?array $checklistInput = null;
    public ?array $kitItemsInput = null;

    protected $fillable = [
        'calibrated_item_id',
        'calibrated_item_type',
        'checklist_id',
        'calibration_date',
        'type',
        'result',
        'k_factor',
        'deviation',
        'uncertainty',
        'temperature',
        'humidity',
        'notes',
        'certificate_path',
        'performed_by_id',
        'provider_id',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'result' => \Modules\Metrology\Enums\CalibrationResult::class,
    ];

    /**
     * Automação: Ao criar uma calibração, atualiza o vencimento do item.
     */
    /**
     * Map of events to classes.
     */
    protected $dispatchesEvents = [
        'saved' => \Modules\Metrology\Events\CalibrationSaved::class,
    ];

    /**
     * Relação Polimórfica (Pode ser Instrumento ou Padrão de Referência)
     */
    public function calibratedItem(): MorphTo
    {
        return $this->morphTo();
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'provider_id');
    }

    protected static function factory(): CalibrationFactory
    {
        return CalibrationFactory::new();
    }
}
