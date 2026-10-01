<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Database\Factories\InstrumentFactory;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\InstrumentCriticality;
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
 * @property ?string $asset_number
 * @property ?string $tenant_id
 * @property ?string $stock_number
 * @property ?string $serial_number
 * @property ItemStatus $status
 * @property InstrumentCriticality $criticality
 * @property ?Carbon $calibration_due
 * @property ?Carbon $acquisition_date
 * @property ?Carbon $next_calibration_date
 * @property ?string $current_supplier_id
 * @property ?string $current_station_id
 * @property ?string $instrument_type_id
 * @property ?string $material_id
 * @property ?string $lab_client_id
 * @property ?float $guard_band_multiplier_override
 * @property ?InstrumentType $instrumentType
 * @property ?Station $station
 * @property ?Supplier $currentSupplier
 * @property ?Material $material
 * @property ?LabClient $labClient
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
        'criticality',
        'nfc_tag',
        'current_station_id',
        'current_supplier_id',
        'image_path',
        'material_id',
        'tenant_id',
        'lab_client_id',
        'guard_band_multiplier_override',
    ];

    protected $attributes = [
        'criticality' => 'operational_reference',
    ];

    protected $casts = [
        'mpe_value' => 'float',
        'calibration_due' => 'datetime',
        'acquisition_date' => 'datetime',
        'next_calibration_date' => 'datetime',
        'status' => ItemStatus::class,
        'criticality' => InstrumentCriticality::class,
        'guard_band_multiplier_override' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Instrument $instrument): void {
            if ($instrument->calibration_due !== null) {
                return;
            }

            $baseDate = $instrument->acquisition_date ?? now();
            $months = $instrument->getCalibrationFrequencyMonths();
            $instrument->calibration_due = Carbon::parse($baseDate)->addMonths($months);
        });
    }

    public function getCriticalityAttribute($value): InstrumentCriticality
    {
        if ($value instanceof InstrumentCriticality) {
            return $value;
        }

        if (is_string($value)) {
            return InstrumentCriticality::tryFrom($value) ?? InstrumentCriticality::OperationalReference;
        }

        return InstrumentCriticality::OperationalReference;
    }

    public function isCritical(): bool
    {
        return $this->criticality->isSafetyOrQualityCritical();
    }

    /**
     * @return MorphMany<Calibration, $this>
     */
    public function calibrations(): MorphMany
    {
        return $this->morphMany(Calibration::class, 'calibrated_item');
    }

    /**
     * @return HasMany<InstrumentMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(InstrumentMovement::class);
    }

    /**
     * @return MorphMany<WorkOrder, $this>
     */
    public function workOrders(): MorphMany
    {
        return $this->morphMany(WorkOrder::class, 'item');
    }

    /**
     * Retorna a Não-Conformidade ativa (aberta/investigando) mais recente.
     *
     * @return MorphOne<NonConformity, $this>
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
     * @return BelongsTo<InstrumentType, $this>
     */
    public function instrumentType(): BelongsTo
    {
        return $this->belongsTo(InstrumentType::class);
    }

    /**
     * @return BelongsTo<Station, $this>
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'current_station_id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function currentSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'current_supplier_id');
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<LabClient, $this>
     */
    public function labClient(): BelongsTo
    {
        return $this->belongsTo(LabClient::class, 'lab_client_id');
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
        return $this->instrumentType->decision_rule ?? 'simple';
    }

    public function getCalibrationFrequencyMonths(): int
    {
        return $this->instrumentType->calibration_frequency_months ?? 12;
    }

    public function getDecisionRuleStrategy(): DecisionRuleStrategy
    {
        $rule = $this->getDecisionRule();
        $multiplier = (float) ($this->guard_band_multiplier_override ?? $this->instrumentType->guard_band_multiplier ?? 1.0);

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
