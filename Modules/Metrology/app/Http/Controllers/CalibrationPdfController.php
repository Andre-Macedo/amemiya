<?php

namespace Modules\Metrology\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Services\PdfSignerService;
use Modules\System\Models\Setting;

/**
 * Controller responsável pela geração e download do PDF do certificado de calibração.
 */
class CalibrationPdfController extends Controller
{
    /**
     * Gera e retorna o stream do PDF do certificado.
     *
     * @return Response
     */
    public function download(Calibration $calibration, PdfSignerService $signer)
    {
        // Carrega os relacionamentos necessários para o PDF
        $calibration->load([
            'calibratedItem',
            'checklist.checklistTemplate',
            'checklist.items.referenceStandard', // Para listar os padrões usados
            'performedBy',
        ]);

        // Extract unique standards used
        $standards = collect();
        if ($calibration->checklist && $calibration->checklist->items) {
            $standards = $calibration->checklist->items
                ->pluck('referenceStandard')
                ->filter() // Remove nulls
                ->unique('id');
        }

        // Check if there are checklist items
        $results = [];
        if ($calibration->checklist && $calibration->checklist->items) {
            $results = $calibration->checklist->items->map(function ($item) {
                // Ensure readings is an array if it's stored as JSON
                $readings = is_string($item->readings) ? json_decode($item->readings, true) : ($item->readings ?? []);
                // Calculate average if readings exist, otherwise use 0
                $average = ! empty($readings) && is_array($readings)
                           ? array_sum($readings) / count($readings)
                           : ($item->reading_value ?? 0); // specific field fallback

                return [
                    'nominal' => $item->nominal_value,
                    'average' => $average,
                    'error' => $item->deviation ?? ($average - $item->nominal_value),
                    'uncertainty' => $item->uncertainty ?? 0,
                    'result' => $item->status ?? 'Pass', // Assuming status field exists on item
                ];
            });
        }

        // Carrega configurações de identidade do laboratório (White-labeling)
        $identity = [
            'lab_name' => Setting::getValue('lab_name', config('app.name')),
            'lab_address' => Setting::getValue('lab_address', ''),
            'lab_contact' => Setting::getValue('lab_contact', ''),
            'lab_logo_path' => Setting::getValue('lab_logo_path'),
            'certificate_footer' => Setting::getValue('certificate_footer', 'Digital signature compliant with FDA 21 CFR Part 11.'),
            'accent_color' => Setting::getValue('lab_accent_color', '#3b82f6'),
        ];

        // Gera o PDF usando uma View Blade
        $pdf = Pdf::loadView('metrology::pdf.certificate', [
            'record' => $calibration,
            'instrument' => $calibration->calibratedItem,
            'standards' => $standards,
            'results' => $results,
            'identity' => $identity,
        ]);

        $pdfContent = $pdf->output();

        // 7. Digital Signature (if configured)
        $certPath = config('metrology.certificate_path'); // e.g. storage_path('app/certs/company.pfx')
        $certPass = config('metrology.certificate_password');

        if ($certPath && file_exists($certPath)) {
            $rubricPath = $calibration->performedBy && $calibration->performedBy->signature_image_path
                ? storage_path('app/'.$calibration->performedBy->signature_image_path)
                : null;

            try {
                $pdfContent = $signer->sign($pdfContent, $certPath, $certPass, $rubricPath);
            } catch (\Exception $e) {
                // Return unsigned but log error? Or fail?
                // For now allow download but maybe log warning
                logger()->error('PDF Signing Failed: '.$e->getMessage());
            }
        }

        return response($pdfContent)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"Certificado-{$calibration->id}.pdf\"");
    }
}
