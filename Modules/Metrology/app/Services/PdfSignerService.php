<?php

namespace Modules\Metrology\Services;

use setasign\Fpdi\Tcpdf\Fpdi;

class PdfSignerService
{
    /**
     * Signs a PDF content string with a digital certificate and optionally a user signature image.
     *
     * @param  string  $pdfContent  Raw PDF content
     * @param  string  $certificatePath  Absolute path to .pfx
     * @param  string  $password  PFX password
     * @param  string|null  $rubricImagePath  Absolute path to user's rubric image signature
     * @return string Signed PDF content
     */
    public function sign(string $pdfContent, string $certificatePath, string $password, ?string $rubricImagePath = null): string
    {
        // 1. Initialize FPDI extended with TCPDF
        $pdf = new Fpdi;

        // 2. Set document info
        $pdf->SetCreator('Amemiya Metrology System');
        $pdf->SetAuthor('Amemiya');
        $pdf->SetTitle('Calibration Certificate');

        // 3. Import pages from the source PDF
        // We need to save it to a temporary file because FPDI reads from file
        $tmpFile = tempnam(sys_get_temp_dir(), 'pdf_sign_').'.pdf';
        file_put_contents($tmpFile, $pdfContent);

        $pageCount = $pdf->setSourceFile($tmpFile);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);

            // Get the size of the imported page
            $size = $pdf->getTemplateSize($templateId);

            // Add a page with the same orientation and size
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);

            // Use the imported page
            $pdf->useTemplate($templateId);

            // 4. Add Rubric Image (if provided and on the last page usually? Or all? Let's assume Technician signs)
            // 4. Add Rubric Image
            if ($rubricImagePath && $pageNo === $pageCount && file_exists($rubricImagePath)) {
                $pos = config('metrology.pdf_rubric_position', ['x' => 140, 'y' => 250, 'w' => 40]);
                $pdf->Image($rubricImagePath, (float) $pos['x'], (float) $pos['y'], (float) $pos['w'], 0, 'PNG');
            }
        }

        unlink($tmpFile);

        // 5. Apply Digital Signature
        // 'file':// is vital for TCPDF if path is local
        // info keys: Name, Location, Reason, ContactInfo
        $info = [
            'Name' => 'Amemiya Metrology',
            'Location' => 'Sao Paulo, BR',
            'Reason' => 'Calibration Certificate Integrity',
            'ContactInfo' => 'metrology@amemiya.com',
        ];

        // Enable cryptographic signature
        $pdf->setSignature('file://'.$certificatePath, 'file://'.$certificatePath, $password, '', 2, $info);

        // 6. Output signed PDF as string
        // 'S' = return as string
        return $pdf->Output('signed_certificate.pdf', 'S');
    }
}
