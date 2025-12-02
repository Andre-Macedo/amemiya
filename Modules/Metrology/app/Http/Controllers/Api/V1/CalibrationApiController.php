<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\Metrology\Http\Resources\CalibrationApiResource;
use Modules\Metrology\Models\Calibration;

class CalibrationApiController extends Controller
{
    /**
     * Retorna os detalhes de uma calibração específica.
     *
     * @param  mixed $id
     * @return CalibrationApiResource
     */
    public function show($id)
    {
        // Busca a calibração e carrega o relacionamento 'performedBy' (usuário) e 'calibratedItem'
        $calibration = Calibration::with(['performedBy', 'calibratedItem'])->findOrFail($id);

        return new CalibrationApiResource($calibration);
    }
}
