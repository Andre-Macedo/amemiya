<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PrintBatchInstrumentLabelsAction
{
    /**
     * Gera um único PDF contendo múltiplas etiquetas com QR Code, uma por página.
     *
     * @param  Collection  $records  Uma coleção de modelos Instrument.
     * @return string O conteúdo binário do PDF da etiqueta.
     */
    public function execute(Collection $records): string
    {
        $baseUrl = config('app.frontend_url', config('app.url'));

        // Prepare data for each label
        $labelsData = $records->map(function ($record) use ($baseUrl) {
            $url = "{$baseUrl}/verify/{$record->id}";
            $qrCode = QrCode::size(100)->generate($url);

            return [
                'record' => $record,
                'qrCode' => $qrCode,
            ];
        });

        // Use a new view that iterates over labels and adds page breaks
        $pdf = Pdf::loadView('metrology::pdf.label-batch', [
            'labels' => $labelsData,
        ]);

        // Configura papel personalizado para impressora térmica (50x30mm)
        $pdf->setPaper([0, 0, 141.732, 85.0394], 'landscape'); // aprox 50mm x 30mm em pontos (1mm = 2.83pt)

        return $pdf->output();
    }
}
