<?php

namespace Modules\Metrology\Models;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Metrology\Database\Factories\CalibrationFactory;

class Calibration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'calibrated_item_id',
        'calibrated_item_type',
        'checklist_id',
        'calibration_date',
        'type',
        'result',
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

    /**
     * Automação: Ao criar uma calibração, atualiza o vencimento do item.
     */
    protected static function booted(): void
    {
        static::saving(function (Calibration $calibration) {
            $item = $calibration->calibratedItem;

            if ($item instanceof Instrument && $calibration->deviation !== null && $item->uncertainty) {

                $measuredError = abs((float)$calibration->deviation);

                $limitString = preg_replace('/[^0-9.]/', '', str_replace(',', '.', $item->uncertainty));
                $limit = (float)$limitString;

                if ($limit > 0) {
                    if ($measuredError > $limit) {
                        $calibration->result = 'rejected';
                    } else {
                        if ($calibration->result !== 'approved_with_restrictions') {
                            $calibration->result = 'approved';
                        }
                    }
                }
            }
        });

        static::saved(function (Calibration $calibration) {
            $item = $calibration->calibratedItem;

            if ($item) {
                // --- CENÁRIO A: APROVADO ---
                if (in_array($calibration->result, ['approved', 'approved_with_restrictions'])) {

                    // 1. Calcula Data de Vencimento
                    $months = 12; // Default
                    if ($item instanceof \Modules\Metrology\Models\Instrument) {
                        $months = $item->instrumentType->calibration_frequency_months ?? 12;
                    } elseif ($item instanceof \Modules\Metrology\Models\ReferenceStandard) {
                        $months = $item->referenceStandardType->calibration_frequency_months ?? 24;
                    }
                    $nextDate = $calibration->calibration_date->copy()->addMonths($months);

                    // 2. Prepara dados de atualização comum
                    $updateData = [
                        'calibration_due' => $nextDate,
                        'status' => 'active', // Volta a ficar ativo
                    ];

                    // 3. Lógica Específica para PADRÃO (Atualizar Valor Real)
                    if ($item instanceof \Modules\Metrology\Models\ReferenceStandard) {
                        // Se o form mandou um 'deviation' (erro), somamos ao nominal
                        if ($item->nominal_value && $calibration->deviation !== null) {
                            $updateData['actual_value'] = $item->nominal_value + $calibration->deviation;
                        }
                        // Se a calibração trouxe nova incerteza
                        if ($calibration->uncertainty) {
                            $updateData['uncertainty'] = $calibration->uncertainty;
                        }
                    }

                    // 4. Salva a atualização no Pai
                    $item->update($updateData);

                    // 5. Se for um KIT (Padrão Pai), atualiza todos os filhos (Cascata)
                    if ($item instanceof \Modules\Metrology\Models\ReferenceStandard && $item->children()->exists()) {
                        $item->children()->update([
                            'calibration_due' => $nextDate,
                            'status' => 'active', // Filhos também ficam ativos
                            // Nota: Não atualizamos actual_value dos filhos aqui em massa,
                            // isso é feito pelo Repeater no formulário de calibração.
                        ]);
                    }
                } // --- CENÁRIO B: REPROVADO ---
                elseif ($calibration->result === 'rejected') {
                    $item->update(['status' => 'rejected']);

                    // Se o Kit reprovou, os filhos também reprovam (segurança)
                    if ($item instanceof \Modules\Metrology\Models\ReferenceStandard && $item->children()->exists()) {
                        $item->children()->update(['status' => 'rejected']);
                    }
                }
            }

            // Limpeza de Fornecedor (Comum a todos)
            if ($item instanceof \Modules\Metrology\Models\Instrument) {
                $item->update(['current_supplier_id' => null]);
            }
        });
    }
}
