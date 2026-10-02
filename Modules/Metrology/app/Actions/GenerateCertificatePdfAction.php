<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Services\PdfSignerService;
use Modules\System\Models\Setting;
use Modules\System\Models\User;
use Throwable;

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

        $prepared = app(PrepareCertificateDataAction::class)->execute($calibration);
        $standards = $prepared['standards'];
        $results = $prepared['results'];

        // Fallback para calibrações externas sem checklist ponto a ponto
        $hasDeviation = $calibration->deviation !== null || $calibration->as_left_deviation !== null;
        if (empty($results) && $hasDeviation) {
            $isApproved = in_array($calibration->result, [
                CalibrationResult::Approved,
                CalibrationResult::ApprovedWithRestrictions,
            ], true);

            $results[] = [
                'step' => 'Resultado Global / Calibração Externa',
                'nominal' => $calibration->nominal_value ?? 0.0,
                'average' => ($calibration->nominal_value ?? 0.0) + (float) ($calibration->as_left_deviation ?? $calibration->deviation ?? 0.0),
                'error' => (float) ($calibration->as_left_deviation ?? $calibration->deviation ?? 0.0),
                'uncertainty' => (float) ($calibration->uncertainty ?? 0.0),
                'k_factor' => 2.0,
                'result' => $isApproved ? 'Approved' : 'Rejected',
            ];
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

        if ($certPath) {
            if (file_exists($certPath)) {
                $rubricPath = null;
                $performer = $calibration->performedBy;

                if ($performer instanceof User) {
                    if ($performer->signature_image_path) {
                        $rubricPath = storage_path("app/{$performer->signature_image_path}");
                    }
                }

                try {
                    $pdfContent = $this->signer->sign($pdfContent, $certPath, $certPass, $rubricPath);
                } catch (Throwable $exception) {
                    logger()->error("PDF Signing Failed: {$exception->getMessage()}");
                }
            }
        }

        // Calcula e persiste o hash criptográfico SHA-256 para garantia de integridade (ISO 17025)
        $pdfHash = hash('sha256', $pdfContent);
        if ($calibration->exists && $calibration->pdf_hash !== $pdfHash) {
            $calibration->withoutEvents(function () use ($calibration, $pdfHash) {
                $calibration->updateQuietly(['pdf_hash' => $pdfHash]);
            });
        }

        return $pdfContent;
    }
}
