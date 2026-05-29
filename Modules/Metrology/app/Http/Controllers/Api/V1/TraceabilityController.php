<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;

class TraceabilityController extends Controller
{
    public function show($calibrationId)
    {
        $rootCalibration = Calibration::with(['calibratedItem', 'checklist.items.referenceStandard', 'provider'])->findOrFail($calibrationId);

        $nodes = [];
        $edges = [];

        // Adiciona o nó raiz (O Instrumento Calibrado)
        $rootNodeId = "cal-{$rootCalibration->id}";
        $nodes[] = [
            'id' => $rootNodeId,
            'type' => 'instrument',
            'data' => [
                'label' => $rootCalibration->calibratedItem->name ?? 'Unknown Item',
                'sublabel' => $rootCalibration->certificate_number,
                'date' => $rootCalibration->calibration_date->format('Y-m-d'),
                'status' => $rootCalibration->result,
                'provider' => $rootCalibration->provider?->name ?? 'Internal Laboratory',
            ],
            'position' => ['x' => 250, 'y' => 0],
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
        if ($level > 5) {
            return;
        }

        $standards = collect();
        if ($calibration->checklist) {
            foreach ($calibration->checklist->items as $item) {
                if ($item->referenceStandard) {
                    $standards->push($item->referenceStandard);
                }
            }
        }

        $standards = $standards->unique('id');

        $xOffset = 0;
        foreach ($standards as $standard) {
            $stdCalibration = Calibration::with('provider')
                ->where('calibrated_item_type', ReferenceStandard::class)
                ->where('calibrated_item_id', $standard->id)
                ->where('calibration_date', '<=', $calibration->calibration_date)
                ->where('result', '!=', 'rejected')
                ->latest('calibration_date')
                ->first();

            $nodeId = "std-{$standard->id}-".($stdCalibration ? $stdCalibration->id : 'no-cal');

            $exists = collect($nodes)->contains('id', $nodeId);
            if (! $exists) {
                $nodes[] = [
                    'id' => $nodeId,
                    'type' => 'standard',
                    'data' => [
                        'label' => $standard->name,
                        'sublabel' => $stdCalibration ? $stdCalibration->certificate_number : 'No Cert Found',
                        'date' => $stdCalibration ? $stdCalibration->calibration_date->format('Y-m-d') : null,
                        'status' => $stdCalibration ? $stdCalibration->result : 'unknown',
                        'provider' => $stdCalibration?->provider?->name ?? ($stdCalibration ? 'Internal Laboratory' : 'Unknown Source'),
                        'is_external' => $stdCalibration?->type === 'external',
                    ],
                    'position' => ['x' => 250 + ($xOffset * 250), 'y' => 150 * $level],
                ];
            }

            $edges[] = [
                'id' => "e-{$parentNodeId}-{$nodeId}",
                'source' => $nodeId,
                'target' => $parentNodeId,
                'animated' => true,
            ];

            if ($stdCalibration) {
                $this->buildChain($stdCalibration, $nodeId, $nodes, $edges, $level + 1);
            }

            $xOffset++;
        }
    }
}
