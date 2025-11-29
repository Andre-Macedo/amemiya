<?php

namespace Modules\Metrology\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Metrology\Models\Calibration;

class CalibrationPdfController extends Controller
{
    public function download(Calibration $calibration)
    {
        // Carrega os relacionamentos necessários para o PDF
        $calibration->load([
            'calibratedItem.manufacturer', // Supplier
            'checklist.checklistTemplate',
            'checklist.items.referenceStandard', // Para listar os padrões usados
            'performedBy',
        ]);

        // Gera o PDF usando uma View Blade
        $pdf = Pdf::loadView('metrology::pdf.certificate', [
            'record' => $calibration,
            'instrument' => $calibration->calibratedItem,
        ]);

        return $pdf->stream("Certificado-{$calibration->id}.pdf");
    }
}
