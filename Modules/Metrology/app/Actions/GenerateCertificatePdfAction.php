<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Services\PdfSignerService;
use Modules\System\Models\Setting;

class GenerateCertificatePdfAction
{
    public function __construct(
        protected PdfSignerService $signer
    ) {}

    /**
     * Gera o conteúdo binário em PDF do certificado de calibração assinado digitalmente.
     */
    public function execute(Calibration $calibration): string
    {
        $calibration->loadMissing([
            'calibratedItem',
            'checklist.checklistTemplate',
            'checklist.items.referenceStandard',
            'performedBy',
        ]);

        $standards = collect();
        if ($calibration->checklist && $calibration->checklist->items) {
            $standards = $calibration->checklist->items
                ->pluck('referenceStandard')
                ->filter()
                ->unique('id');
        }

        $results = [];
        if ($calibration->checklist && $calibration->checklist->items) {
            $results = $calibration->checklist->items->map(function ($item) {
                $readings = is_string($item->readings) ? json_decode($item->readings, true) : ($item->readings ?? []);
                $average = ! empty($readings) && is_array($readings)
                    ? array_sum($readings) / count($readings)
                    : ($item->reading_value ?? 0);

                return [
                    'nominal' => $item->nominal_value,
                    'average' => $average,
                    'error' => $item->deviation ?? ($average - $item->nominal_value),
                    'uncertainty' => $item->uncertainty ?? 0,
                    'result' => $item->status ?? 'Pass',
                ];
            });
        }

        $identity = [
            'lab_name' => Setting::getValue('lab_name', config('app.name')),
            'lab_address' => Setting::getValue('lab_address', ''),
            'lab_contact' => Setting::getValue('lab_contact', ''),
            'lab_logo_path' => Setting::getValue('lab_logo_path'),
            'certificate_footer' => Setting::getValue('certificate_footer', 'Digital signature compliant with FDA 21 CFR Part 11.'),
            'accent_color' => Setting::getValue('lab_accent_color', '#3b82f6'),
        ];

        $pdf = Pdf::loadView('metrology::pdf.certificate', [
            'record' => $calibration,
            'instrument' => $calibration->calibratedItem,
            'standards' => $standards,
            'results' => $results,
            'identity' => $identity,
        ]);

        $pdfContent = $pdf->output();

        // Assinatura Digital (se configurada)
        $certPath = config('metrology.certificate_path');
        $certPass = config('metrology.certificate_password');

        if ($certPath && file_exists($certPath)) {
            $rubricPath = $calibration->performedBy && $calibration->performedBy->signature_image_path
                ? storage_path('app/'.$calibration->performedBy->signature_image_path)
                : null;

            try {
                $pdfContent = $this->signer->sign($pdfContent, $certPath, $certPass, $rubricPath);
            } catch (\Throwable $e) {
                logger()->error('PDF Signing Failed: '.$e->getMessage());
            }
        }

        return $pdfContent;
    }
}
