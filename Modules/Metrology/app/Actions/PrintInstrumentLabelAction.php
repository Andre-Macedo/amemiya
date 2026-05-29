<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Metrology\Models\Instrument;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PrintInstrumentLabelAction
{
    /**
     * Gera uma etiqueta PDF para impressão térmica (50x30mm) com QR Code.
     *
     * O QR Code aponta para a visualização pública do instrumento.
     *
     * @param  Instrument  $record  O instrumento para o qual a etiqueta será gerada.
     * @return string O conteúdo binário do PDF da etiqueta.
     */
    public function execute(Instrument $record): string
    {
        // URL para visualização pública do instrumento no Frontend (Next.js)
        // Usa a variável de ambiente FRONTEND_URL ou fallback para APP_URL
        $baseUrl = config('app.frontend_url', config('app.url'));

        // Aponta para a rota pública de verificação
        $url = "{$baseUrl}/verify/{$record->id}";

        // Gera o QR Code em formato SVG string
        $qrCode = QrCode::size(100)->generate($url);

        // Renderiza o PDF
        $pdf = Pdf::loadView('metrology::pdf.label', [
            'record' => $record,
            'qrCode' => $qrCode,
        ]);

        // Configura papel personalizado para impressora térmica (50x30mm)
        $pdf->setPaper([0, 0, 141.732, 85.0394], 'landscape'); // aprox 50mm x 30mm em pontos (1mm = 2.83pt)

        return $pdf->output();
    }
}
