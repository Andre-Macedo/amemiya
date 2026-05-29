<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;

class RectifyCalibrationAction
{
    /**
     * Cria uma retificação (emenda) para uma calibração existente.
     *
     * Clona a calibração original e seus checklists, marcando a nova como rascunho
     * e vinculando-a à original para manter o histórico de rastreabilidade.
     * A nova calibração substitui logicamente a anterior.
     *
     * @param  Calibration  $source  A calibração original a ser retificada.
     * @return Calibration A nova instância de calibração (Rascunho).
     */
    public function execute(Calibration $source): Calibration
    {
        // 1. Clona os dados básicos
        $newCalibration = $source->replicate([
            'id',
            'created_at',
            'updated_at',
            'approved_by_id',
            'approved_at',
            'certificate_path', // Novo certificado será gerado
            'status',
            'replaces_calibration_id',
            'amendment_reason',
        ]);

        $newCalibration->status = 'draft';
        $newCalibration->replaces_calibration_id = $source->id;
        $newCalibration->performed_by_id = auth()->id() ?? $source->performed_by_id;
        $newCalibration->calibration_date = now(); // Data da emenda ou mantém original? Norma diz que é nova data de emissão, mas dados originais. Mantemos now() como data de criação do registro.

        $newCalibration->save();

        // 2. Clona o Checklist (se houver)
        if ($source->checklist) {
            $this->cloneChecklist($source->checklist, $newCalibration);
        }

        return $newCalibration;
    }

    private function cloneChecklist(Checklist $sourceChecklist, Calibration $newCalibration): void
    {
        $newChecklist = $sourceChecklist->replicate(['id', 'calibration_id', 'created_at', 'updated_at']);
        $newChecklist->calibration_id = $newCalibration->id;
        $newChecklist->save();

        // Vincula à nova calibração
        $newCalibration->checklist_id = $newChecklist->id;
        $newCalibration->save();

        // Clona itens
        foreach ($sourceChecklist->items as $item) {
            $newItem = $item->replicate(['id', 'checklist_id', 'created_at', 'updated_at']);
            $newItem->checklist_id = $newChecklist->id;
            $newItem->save();
        }
    }
}
