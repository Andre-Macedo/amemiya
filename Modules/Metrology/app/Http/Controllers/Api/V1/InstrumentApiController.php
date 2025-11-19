<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // Importante: Adicione esta importação
use Illuminate\Database\Eloquent\Builder;
use Modules\Metrology\Http\Resources\InstrumentApiResource;
use Modules\Metrology\Models\Instrument;

class InstrumentApiController extends Controller
{
    /**
     * Retorna uma lista paginada dos instrumentos com filtros.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $query = Instrument::with(['station', 'calibrations']);

        if ($request->filled('search')) {
            $searchTerm = $request->input('search');

            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('serial_number', 'like', "%{$searchTerm}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'Todos') {
            $query->where('status', $request->input('status'));
        }

        $query->latest();

        $instruments = $query->paginate(20);

        $instruments->appends($request->all());

        return InstrumentApiResource::collection($instruments);
    }

    /**
     * Retorna os detalhes de um instrumento específico.
     *
     * @param  \Modules\Metrology\Models\Instrument  $instrument
     * @return InstrumentApiResource
     */
    public function show(Instrument $instrument)
    {
        $instrument->load(['station', 'calibrations' => function ($query) {
            $query->latest('calibration_date');
        }]);

        return new InstrumentApiResource($instrument);
    }
}
