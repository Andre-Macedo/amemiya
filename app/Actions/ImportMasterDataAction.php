<?php

namespace App\Actions;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\InstrumentType;
use Modules\Metrology\Models\Material;

class ImportMasterDataAction
{
    /**
     * Clona os dados mestres globais para um tenant específico.
     */
    public function execute(string $tenantId): void
    {
        DB::transaction(function () use ($tenantId) {

            // 1. Clonar Materiais
            $materials = Material::whereNull('tenant_id')->get();
            foreach ($materials as $material) {
                $newMaterial = $material->replicate(['id']);
                $newMaterial->tenant_id = $tenantId;
                $newMaterial->save();
            }

            // 2. Clonar Tipos de Instrumentos
            $instrumentTypes = InstrumentType::whereNull('tenant_id')->get();
            foreach ($instrumentTypes as $type) {
                $newType = $type->replicate(['id']);
                $newType->tenant_id = $tenantId;
                $newType->save();

                // 3. Clonar Checklist Templates vinculados ao Tipo
                $templates = ChecklistTemplate::where('instrument_type_id', $type->id)
                    ->whereNull('tenant_id')
                    ->get();

                foreach ($templates as $template) {
                    $newTemplate = $template->replicate(['id']);
                    $newTemplate->tenant_id = $tenantId;
                    $newTemplate->instrument_type_id = $newType->id;
                    $newTemplate->save();

                    // 4. Clonar Itens do Checklist
                    foreach ($template->items as $item) {
                        $newItem = $item->replicate(['id']);
                        $newItem->tenant_id = $tenantId;
                        $newItem->checklist_template_id = $newTemplate->id;
                        $newItem->save();
                    }
                }
            }
        });
    }
}
