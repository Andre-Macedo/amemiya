<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Metrology\Http\Resources\InstrumentApiResource;
use Modules\Metrology\Models\Instrument;

class AccessLogApiController extends Controller
{
    /**
     * Retorna uma lista paginada dos instrumentos.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
//        $instruments = Instrument::with(['station', 'calibrations'])->paginate(20);
//
//        return InstrumentApiResource::collection($instruments);
    }

    /**
     * Retorna os detalhes de um instrumento específico.
     *
     * @param  \Modules\Metrology\Models\Instrument  $instrument
     * @return InstrumentApiResource
     */
    public function show(Instrument $instrument)
    {
//        $instrument->load(['station', 'calibrations']);
//
//        return new InstrumentApiResource($instrument);
    }

}
