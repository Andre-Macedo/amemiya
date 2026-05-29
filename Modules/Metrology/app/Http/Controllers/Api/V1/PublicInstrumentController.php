<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Modules\Metrology\Models\Instrument;

class PublicInstrumentController extends Controller
{
    /**
     * Retorna dados públicos de validação do instrumento.
     * Acessível sem autenticação.
     */
    public function show($id)
    {
        $instrument = Instrument::with(['calibrations' => function ($query) {
            $query->where('result', '!=', 'rejected')
                  ->latest('calibration_date')
                  ->limit(1);
        }])->findOrFail($id);

        $lastCalibration = $instrument->calibrations->first();

        return response()->json([
            'id' => $instrument->id,
            'name' => $instrument->name,
            'serial_number' => $instrument->serial_number,
            'stock_number' => $instrument->stock_number,
            'manufacturer' => $instrument->manufacturer,
            'model' => $instrument->model,
            'status' => $instrument->status,
            'calibration_due' => $instrument->calibration_due,
            'last_calibration_date' => $lastCalibration?->calibration_date,
            'certificate_url' => $lastCalibration?->certificate_path, // Link para download (pode precisar de rota assinada se for privado)
            'is_valid' => $instrument->status === 'active' && $instrument->calibration_due?->isFuture(),
        ]);
    }
}
