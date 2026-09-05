<?php

declare(strict_types=1);

namespace Modules\Metrology\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Modules\Metrology\Actions\GenerateCertificatePdfAction;
use Modules\Metrology\Models\Calibration;

/**
 * Controller responsável pela geração e download do PDF do certificado de calibração.
 */
class CalibrationPdfController extends Controller
{
    /**
     * Gera e retorna o stream do PDF do certificado.
     */
    public function download(Calibration $calibration, GenerateCertificatePdfAction $generator): Response
    {
        $pdfContent = $generator->execute($calibration);

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"Certificado-{$calibration->id}.pdf\"");
    }
}
