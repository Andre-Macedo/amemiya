<?php

namespace Modules\Metrology\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Metrology\Models\Calibration;

class PublicCalibrationController extends Controller
{
    /**
     * Verifica a validade de um certificado através de seu hash público.
     */
    public function show(string $hash): JsonResponse
    {
        $calibration = Calibration::where('verification_hash', $hash)
            ->where('status', 'published')
            ->first();

        if (! $calibration) {
            return response()->json([
                'valid' => false,
                'message' => 'Certificado não encontrado ou não publicado.',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'certificate' => [
                'id' => $calibration->id,
                'calibration_date' => $calibration->calibration_date->format('d/m/Y'),
                'result' => $calibration->result->getLabel(),
                'result_value' => $calibration->result->value,
                'technician' => $calibration->technician,
                'instrument' => [
                    'name' => $calibration->calibratedItem?->name,
                    'serial_number' => $calibration->calibratedItem?->serial_number,
                ],
                'tenant' => [
                    'name' => $calibration->tenant?->name,
                ],
                'verification_date' => now()->toDateTimeString(),
            ],
        ]);
    }
}
