<?php

declare(strict_types=1);

namespace Modules\Metrology\Models;

use App\Traits\BelongsToTenant;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Modules\Metrology\Contracts\CalibratableItem;
use Modules\Metrology\Database\Factories\ReferenceStandardFactory;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Services\DecisionRules\DecisionRuleStrategy;
use Modules\Metrology\Services\DecisionRules\SimpleAcceptance;
use Modules\Metrology\Traits\HasAssetIdentity;
use Modules\Metrology\Traits\HasAttachments;

/**
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property string|null $serial_number
 * @property string|null $stock_number
 * @property int $reference_standard_type_id
 * @property string|null $description
 * @property Carbon|null $calibration_due
 * @property string $status
 * @property string|null $nominal_value
 * @property string|null $unit
 * @property string|null $actual_value
 * @property string|null $uncertainty
 * @property string|null $grade
 * @property int|null $material_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ReferenceStandard|null $parent
 * @property-read Collection<int, ReferenceStandard> $children
 * @property-read Calibration|null $latestCalibration
 */
class ReferenceStandard extends Model implements CalibratableItem
{
    use BelongsToTenant, HasUlids, LogsActivity;
    use HasAssetIdentity;
    use HasAttachments;

    protected $fillable = [
        'tenant_id',
        'name',
        'parent_id',
        'serial_number',
        'stock_number',
        'reference_standard_type_id',
        'description',
        'calibration_due',
        'status',

        'nominal_value',
        'unit',
        'actual_value',
        'uncertainty',
        'grade',
        'material_id',
    ];

    protected $casts = [
        'nominal_value' => 'decimal:6',
        'actual_value' => 'decimal:6',
        'uncertainty' => 'decimal:6',
        'calibration_due' => 'date',
        'status' => ItemStatus::class,
    ];

    protected $appends = [
        'effective_serial_number',
        'effective_stock_number',
    ];

    /**
     * Tipo do padrão de referência.
     *
     * @return BelongsTo<ReferenceStandardType, ReferenceStandard>
     */
    public function referenceStandardType(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandardType::class);
    }

    /**
     * Padrão pai (se este for componente de um kit).
     *
     * @return BelongsTo<ReferenceStandard, ReferenceStandard>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ReferenceStandard::class, 'parent_id');
    }

    /**
     * Componentes filhos (se este padrão for um kit).
     *
     * @return HasMany<ReferenceStandard>
     */
    public function children(): HasMany
    {
        return $this->hasMany(ReferenceStandard::class, 'parent_id');
    }

    public function calibrations(): MorphMany
    {
        return $this->morphMany(Calibration::class, 'calibrated_item');
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

    public static function factory(): ReferenceStandardFactory
    {
        return ReferenceStandardFactory::new();
    }

    /**
     * Obtém a calibração mais recente deste padrão.
     *
     * @return MorphOne<Calibration>
     */
    public function latestCalibration(): MorphOne
    {
        return $this->morphOne(Calibration::class, 'calibrated_item')->latestOfMany();
    }

    /**
     * @return BelongsTo<Material, ReferenceStandard>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Obtém a URL do certificado ativo (próprio ou herdado do pai).
     */
    public function getActiveCertificateUrlAttribute(): ?string
    {
        // 1. Tenta calibração própria
        if ($this->latestCalibration && $this->latestCalibration->certificate_path) {
            return $this->latestCalibration->certificate_path;
        }

        // 2. Se não tem, e for filho, tenta do Pai
        if ($this->parent_id && $this->parent->latestCalibration) {
            return $this->parent->latestCalibration->certificate_path;
        }

        return null;
    }

    public function getNextCalibrationDueAttribute(): ?Carbon
    {
        /** @var Calibration|null $latestCalibration */
        $latestCalibration = $this->latestCalibration;

        if ($latestCalibration) {
            $months = $this->referenceStandardType->calibration_frequency_months ?? 24;

            return $latestCalibration->calibration_date->copy()->addMonths($months);
        }

        return null; // Retorna null se não houver histórico
    }

    public function getCalibrationFrequencyMonths(): int
    {
        return $this->referenceStandardType->calibration_frequency_months ?? 24;
    }

    public function getMaximumPermissibleError(): ?float
    {
        // Padrões de Referência geralmente não possuem MPE da mesma forma que instrumentos
        // para regras de decisão, a menos que especificado. Retornar null pula a avaliação.
        return null;
    }

    public function getDecisionRule(): string
    {
        return 'simple';
    }

    public function getDecisionRuleStrategy(): DecisionRuleStrategy
    {
        // Estratégia padrão se nenhuma for especificada
        return new SimpleAcceptance;
    }

    public function processCalibrationResult(Calibration $calibration, CalibrationResult $status): void
    {
        if (in_array($status, [CalibrationResult::Approved, CalibrationResult::ApprovedWithRestrictions])) {
            // 1. Calcula Data de Vencimento
            $months = $this->getCalibrationFrequencyMonths();
            $nextDate = $calibration->calibration_date->copy()->addMonths($months);

            // 2. Prepara Dados para Atualização
            $updateData = [
                'calibration_due' => $nextDate,
                'status' => ItemStatus::Active,
            ];

            // 3. Atualiza Valor Real e Incerteza
            if ($this->nominal_value && $calibration->deviation !== null) {
                $updateData['actual_value'] = (float) $this->nominal_value + (float) $calibration->deviation;
            }
            if ($calibration->uncertainty) {
                $updateData['uncertainty'] = $calibration->uncertainty;
            }

            $this->update($updateData);

            // 4. Cascata para Filhos (Kits)
            if ($this->children()->exists()) {
                $this->children()->update([
                    'calibration_due' => $nextDate,
                    'status' => ItemStatus::Active,
                ]);
            }

        } elseif ($status === CalibrationResult::Rejected) {
            $this->update(['status' => ItemStatus::Rejected]);

            if ($this->children()->exists()) {
                $this->children()->update(['status' => ItemStatus::Rejected]);
            }
        }
    }
}
