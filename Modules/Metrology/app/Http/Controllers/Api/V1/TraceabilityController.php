<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;

class TraceabilityController extends Controller
{
    public function show($calibrationId)
    {
        $rootCalibration = Calibration::with(['calibratedItem', 'checklist.items.referenceStandard'])->findOrFail($calibrationId);

        $nodes = [];
        $edges = [];

        // Adiciona o nó raiz (O Instrumento Calibrado)
        $rootNodeId = "cal-{$rootCalibration->id}";
        $nodes[] = [
            'id' => $rootNodeId,
            'type' => 'instrument', // custom node type
            'data' => [
                'label' => $rootCalibration->calibratedItem->name ?? 'Unknown Item',
                'sublabel' => $rootCalibration->certificate_number,
                'date' => $rootCalibration->calibration_date->format('Y-m-d'),
                'status' => $rootCalibration->result,
            ],
            'position' => ['x' => 250, 'y' => 0], // Posição inicial (layout automático fará o resto no front se usarmos dagre)
        ];

        // Inicia a recursão
        $this->buildChain($rootCalibration, $rootNodeId, $nodes, $edges, 1);

        return response()->json([
            'nodes' => $nodes,
            'edges' => $edges,
        ]);
    }

    private function buildChain(Calibration $calibration, string $parentNodeId, array &$nodes, array &$edges, int $level)
    {
        // Evita loops infinitos e profundidade excessiva
        if ($level > 5) return;

        // Identifica os padrões usados nesta calibração
        // A relação está nos ChecklistItems -> ReferenceStandard
        $standards = collect();

        if ($calibration->checklist) {
            foreach ($calibration->checklist->items as $item) {
                if ($item->referenceStandard) {
                    $standards->push($item->referenceStandard);
                }
            }
        }

        // Remove duplicatas (pode ter usado o mesmo padrão em vários passos)
        $standards = $standards->unique('id');

        $xOffset = 0;
        foreach ($standards as $standard) {
            // Busca a calibração deste padrão que estava VÁLIDA na data da calibração pai
            // Ou seja: calibração mais recente do padrão ANTES da data pai
            $stdCalibration = Calibration::where('calibrated_item_type', ReferenceStandard::class)
                ->where('calibrated_item_id', $standard->id)
                ->where('calibration_date', '<=', $calibration->calibration_date)
                ->where('result', '!=', 'rejected')
                ->latest('calibration_date')
                ->first();

            $nodeId = "std-{$standard->id}-" . ($stdCalibration ? $stdCalibration->id : 'no-cal');

            // Verifica se nó já existe para evitar duplicidade visual
            $exists = collect($nodes)->contains('id', $nodeId);
            if (!$exists) {
                $nodes[] = [
                    'id' => $nodeId,
                    'type' => 'standard',
                    'data' => [
                        'label' => $standard->name,
                        'sublabel' => $stdCalibration ? $stdCalibration->certificate_number : 'No Cert Found',
                        'date' => $stdCalibration ? $stdCalibration->calibration_date->format('Y-m-d') : null,
                        'status' => $stdCalibration ? $stdCalibration->result : 'unknown',
                        'is_expired' => false, // TODO: Verificar se estava vencido na data de uso
                    ],
                    'position' => ['x' => 250 * $xOffset, 'y' => 100 * $level],
                ];
            }

            // Cria a aresta (Edge)
            $edges[] = [
                'id' => "e-{$parentNodeId}-{$nodeId}",
                'source' => $nodeId, // A seta vai do Padrão -> Instrumento (Rastreabilidade flui de baixo pra cima ou cima pra baixo? Geralmente Padrão sustenta Instrumento)
                'target' => $parentNodeId,
                'animated' => true,
            ];

            // Recursão: Busca os padrões usados para calibrar ESTE padrão
            if ($stdCalibration) {
                $this->buildChain($stdCalibration, $nodeId, $nodes, $edges, $level + 1);
            }

            $xOffset++;
        }
    }
}
