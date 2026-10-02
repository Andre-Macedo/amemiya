<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Metrology\Http\Resources\IntermediateCheckApiResource;
use Modules\Metrology\Models\IntermediateCheck;

class IntermediateCheckApiController extends Controller
{
    public function index(Request $request, $instrumentId)
    {
        $checks = IntermediateCheck::where('instrument_id', $instrumentId)
            ->with(['performer', 'referenceStandard'])
            ->orderBy('check_date', 'desc')
            ->paginate($request->input('per_page', 20));

        return IntermediateCheckApiResource::collection($checks);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'instrument_id' => 'required|exists:instruments,id',
            'check_date' => 'required|date',
            'result' => 'required|in:passed,failed',
            'nominal_value' => 'nullable|numeric',
            'measured_value' => 'nullable|numeric',
            'deviation' => 'nullable|numeric',
            'reference_standard_id' => 'nullable|exists:reference_standards,id',
            'performed_by' => 'nullable|exists:users,id',
            'temperature' => 'nullable|numeric',
            'humidity' => 'nullable|numeric',
            'notes' => 'nullable|string',
        ]);

        if (! isset($validated['performed_by'])) {
            $validated['performed_by'] = auth()->id(); // Default to current user
        }

        DB::beginTransaction();
        try {
            $check = IntermediateCheck::create($validated);

            DB::commit();

            return new IntermediateCheckApiResource($check->load(['performer', 'referenceStandard']));
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retorna os dados estatísticos da Carta de Controle de Shewhart (ILAC-G24 Método 2).
     */
    public function shewhart(
        string $instrumentId,
        \Modules\Metrology\Services\ShewhartControlChartService $service
    ): \Illuminate\Http\JsonResponse {
        $instrument = \Modules\Metrology\Models\Instrument::findOrFail($instrumentId);
        $analysis = $service->analyze($instrument);

        return response()->json($analysis);
    }
}
