<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;
use Illuminate\Database\Eloquent\Builder;

class StandardImpactApiController extends Controller
{
    /**
     * Retorna a lista de calibrações afetadas por um padrão específico.
     * Rastreabilidade Reversa: Padrão -> Instrumentos Calibrados.
     */
    public function index(Request $request, ReferenceStandard $standard)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        // Busca calibrações onde ALGUM item do checklist usou este padrão
        $query = Calibration::query()
            ->with(['calibratedItem', 'performedBy'])
            ->whereHas('checklist.items', function (Builder $q) use ($standard) {
                $q->where('reference_standard_id', $standard->id);
            })
            // Opcional: Se houver uma tabela pivot direta calibration_reference_standard, adicionar aqui também
            ->where('status', '!=', 'draft'); // Apenas calibrações finalizadas importam

        if ($request->filled('start_date')) {
            $query->whereDate('calibration_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('calibration_date', '<=', $request->input('end_date'));
        }

        $calibrations = $query->latest('calibration_date')->paginate(20);

        return response()->json($calibrations);
    }
}
