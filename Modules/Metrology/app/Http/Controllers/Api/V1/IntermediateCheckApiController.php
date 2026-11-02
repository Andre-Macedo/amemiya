<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Modules\Metrology\Http\Requests\StoreIntermediateCheckRequest;
use Modules\Metrology\Http\Resources\IntermediateCheckApiResource;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\IntermediateCheck;
use Modules\Metrology\Services\ShewhartControlChartService;

class IntermediateCheckApiController extends Controller
{
    /**
     * Lista histórico de checagens intermediárias de um instrumento.
     */
    public function index(Request $request, string $instrumentId): AnonymousResourceCollection
    {
        $checks = IntermediateCheck::where('instrument_id', $instrumentId)
            ->with(['performer', 'referenceStandard'])
            ->orderBy('check_date', 'desc')
            ->paginate((int) $request->input('per_page', 20));

        return IntermediateCheckApiResource::collection($checks);
    }

    /**
     * Registra uma checagem intermediária metrológica (ISO/IEC 17025 6.4.10).
     */
    public function store(StoreIntermediateCheckRequest $request): JsonResponse|IntermediateCheckApiResource
    {
        $validated = $request->validated();

        if (! isset($validated['performed_by'])) {
            $validated['performed_by'] = auth()->id();
        }

        DB::beginTransaction();
        try {
            $check = IntermediateCheck::create($validated);

            DB::commit();

            return new IntermediateCheckApiResource($check->load(['performer', 'referenceStandard']));
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retorna os dados estatísticos da Carta de Controle de Shewhart (ILAC-G24 Método 2).
     */
    public function shewhart(string $instrumentId, ShewhartControlChartService $service): JsonResponse
    {
        $instrument = Instrument::findOrFail($instrumentId);
        $analysis = $service->analyze($instrument);

        return response()->json($analysis);
    }
}
