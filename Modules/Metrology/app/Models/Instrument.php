<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Database\Factories\InstrumentFactory;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Services\DecisionRules\DecisionRuleStrategy;
use Modules\Metrology\Services\DecisionRules\GuardBand;
use Modules\Metrology\Services\DecisionRules\SimpleAcceptance;
use Modules\Metrology\Services\DecisionRules\UncertaintyAccounted;
use Modules\Metrology\Services\MpeCalculator;
use Modules\Metrology\Traits\HasAssetIdentity;
use Modules\Metrology\Traits\HasAttachments;
use Modules\Metrology\Traits\HasStateTransitions;
use Modules\System\Models\Station;
use Modules\System\Models\Supplier;

/**
 * @property string $id
 * @property string $name
 * @property ItemStatus $status
 */
class Instrument extends Model implements CalibratableItem
{
    use BelongsToTenant, HasFactory, HasUlids, SoftDeletes;
    use HasAssetIdentity;
    use HasAttachments;
    use HasStateTransitions;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'stock_number',
        'serial_number',
        'instrument_type_id',
        'mpe',
        'mpe_value',
        'mpe_type',
        'measuring_range',
        'resolution',
        'manufacturer',
        'model',
        'location',
        'acquisition_date',
        'calibration_due',
        'status',
        'nfc_tag',
        'current_station_id',
        'current_supplier_id',
        'image_path',
        'material_id',
        'tenant_id',
        'guard_band_multiplier_override',
    ];

    protected $casts = [
        'mpe_value' => 'float',
        'calibration_due' => 'datetime',
        'acquisition_date' => 'datetime',
        'next_calibration_date' => 'datetime',
        'status' => ItemStatus::class,
        'guard_band_multiplier_override' => 'float',
    ];

    /**
     * @return MorphMany<Calibration>
     */
    public function calibrations(): MorphMany
    {
        return $this->morphMany(Calibration::class, 'calibrated_item');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InstrumentMovement::class);
    }

    /**
     * @return MorphMany<WorkOrder>
     */
    public function workOrders(): MorphMany
    {
        return $this->morphMany(WorkOrder::class, 'item');
    }

    /**
     * Retorna a Não-Conformidade ativa (aberta/investigando) mais recente.
     */
    public function openNonConformity(): MorphOne
    {
        return $this->morphOne(NonConformity::class, 'item')
            ->whereIn('status', ['open', 'investigating', 'resolved']) // Não fechada
            ->latest();
    }

    protected static function factory(): InstrumentFactory
    {
        return InstrumentFactory::new();
    }

    /**
     * @return BelongsTo<InstrumentType, Instrument>
     */
    public function instrumentType(): BelongsTo
    {
        return $this->belongsTo(InstrumentType::class);
    }

    /**
     * @return BelongsTo<Station, Instrument>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'current_station_id');
    }

    /**
     * @return BelongsTo<Supplier, Instrument>
     */
    public function currentSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'current_supplier_id');
    }

    /**
     * @return BelongsTo<Material, Instrument>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Obtém o Erro Máximo Permissível (MPE) como float.
     * Suporta valores absolutos, percentuais (%) e em ppm resolvidos pelo MpeCalculator.
     */
    public function getMaximumPermissibleError(?float $nominalValue = null): ?float
    {
        return MpeCalculator::resolve($this, $nominalValue);
    }

    public function getDecisionRule(): string
    {
        return $this->instrumentType?->decision_rule ?? 'simple';
    }

    public function getCalibrationFrequencyMonths(): int
    {
        return $this->instrumentType?->calibration_frequency_months ?? 12;
    }

    public function getDecisionRuleStrategy(): DecisionRuleStrategy
    {
        $rule = $this->getDecisionRule();
        $multiplier = (float) ($this->guard_band_multiplier_override ?? $this->instrumentType?->guard_band_multiplier ?? 1.0);

        return match ($rule) {
            'guard_band' => new GuardBand($multiplier),
            'uncertainty_accounted' => new UncertaintyAccounted,
            default => new SimpleAcceptance,
        };
    }

    /**
     * Processa o resultado de uma calibração e atualiza o estado do instrumento.
     * Usa a máquina de estados para validação.
     */
    public function processCalibrationResult(Calibration $calibration, CalibrationResult $status): void
    {
        if (in_array($status, [CalibrationResult::Approved, CalibrationResult::ApprovedWithRestrictions])) {
            $months = $this->getCalibrationFrequencyMonths();
            $nextDate = $calibration->calibration_date->copy()->addMonths($months);

            $this->calibration_due = $nextDate;
            $this->current_supplier_id = null;

            if ($this->status !== ItemStatus::Active) {
                $this->transitionTo(ItemStatus::Active);
            }
            $this->save();

        } elseif ($status === CalibrationResult::Rejected) {
            if ($this->status !== ItemStatus::Rejected) {
                $this->transitionTo(ItemStatus::Rejected);
            } else {
                $this->save();
            }
        }
    }
}
