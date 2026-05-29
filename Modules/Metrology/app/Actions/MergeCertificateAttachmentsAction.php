<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Modules\Metrology\Models\Calibration;
use Webklex\PDFMerger\Facades\PDFMergerFacade as PDFMerger;

class MergeCertificateAttachmentsAction
{
    /**
     * Mescla o certificado principal da calibração com os certificados dos padrões utilizados.
     *
     * Gera um único arquivo PDF contendo:
     * 1. O Certificado da Calibração (Capa e Resultados).
     * 2. Os Certificados de cada Padrão de Referência utilizado (anexos).
     *
     * @param  Calibration  $calibration  A calibração para a qual o certificado será gerado.
     * @return string O conteúdo binário do PDF mesclado.
     *
     * @throws \Throwable Se ocorrer algum erro durante a geração ou mesclagem dos PDFs.
     */
    public function execute(Calibration $calibration)
    {
        try {
            $merger = PDFMerger::init();

            // 1. Gera o Certificado da Calibração atual (Capa)
            // Usa a Action existente que prepara os dados e renderiza a view
            $instrument = $calibration->calibratedItem;
            $data = app(PrepareCertificateDataAction::class)->execute($calibration);

            $pdfContent = Pdf::loadView('metrology::pdf.certificate', [
                'calibration' => $calibration,
                'record' => $calibration, // View espera $record
                'instrument' => $instrument,
                'results' => $data['results'],
                'standards' => $data['standards'],
            ])->output();

            // Adiciona o certificado principal (string mode)
            $merger->addString($pdfContent);

            // 2. Busca e anexa os certificados dos Padrões utilizados
            foreach ($data['standards'] as $standard) {
                // Verifica se o padrão tem um arquivo de certificado válido
                // url: 'storage/certificates/abc.pdf'
                $certPath = $standard->active_certificate_url;

                if ($certPath && Storage::disk('public')->exists($certPath)) {
                    $absolutePath = Storage::disk('public')->path($certPath);
                    $merger->addPDF($absolutePath, 'all');
                }
            }

            $merger->merge();

            // Retorna o conteúdo do PDF mesclado para download
            return $merger->save('merged_result.pdf', 'string');

        } catch (\Throwable $e) {
            fwrite(STDERR, 'Error in Action: '.$e->getMessage()."\n".$e->getTraceAsString()."\n");
            throw $e;
        }
    }
}
