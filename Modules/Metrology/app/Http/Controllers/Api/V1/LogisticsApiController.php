<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Metrology\Http\Resources\InstrumentApiResource;
use Modules\Metrology\Services\LogisticsService;

class LogisticsApiController extends Controller
{
    public function __construct(protected LogisticsService $logisticsService) {}

    /**
     * Recebe a leitura de uma Tag (NFC/RFID) e processa a movimentação.
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'tag_id' => ['required', 'string'],
            'to_station_id' => ['nullable', 'exists:stations,id'],
            'metadata' => ['nullable', 'array'],
        ]);

        try {
            $instrument = $this->logisticsService->processScan(
                $request->tag_id,
                $request->to_station_id,
                $request->metadata ?? []
            );

            return response()->json([
                'message' => "Movimentação registrada: {$instrument->name}",
                'instrument' => new InstrumentApiResource($instrument->load('station')),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => "Tag não reconhecida: {$request->tag_id}",
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar tag: '.$e->getMessage(),
            ], 500);
        }
    }
}
