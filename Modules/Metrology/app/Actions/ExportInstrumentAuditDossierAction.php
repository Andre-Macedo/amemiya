<?php

namespace Modules\Metrology\Actions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Metrology\Models\Instrument;
use ZipArchive;

class ExportInstrumentAuditDossierAction
{
    /**
     * Gera um arquivo ZIP com todo o histórico do instrumento.
     *
     * @return string Caminho do arquivo ZIP gerado.
     */
    public function execute(Instrument $instrument): string
    {
        $zip = new ZipArchive;
        $fileName = 'Dossie_'.Str::slug($instrument->name).'_'.now()->format('YmdHis').'.zip';
        $tempPath = storage_path('app/temp/'.$fileName);

        if (! file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // 1. Adicionar Resumo em JSON ou CSV (Metadados do Ativo)
            $summary = [
                'instrument_name' => $instrument->name,
                'serial_number' => $instrument->serial_number,
                'stock_number' => $instrument->stock_number,
                'status' => $instrument->status->value,
                'last_calibration' => $instrument->calibrations()->latest()->first()?->calibration_date?->toDateString(),
                'exported_at' => now()->toDateTimeString(),
            ];
            $zip->addFromString('RESUMO_ATIVO.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // 2. Adicionar Certificados de Calibração (PDFs)
            $calibrations = $instrument->calibrations()->where('status', 'published')->get();
            foreach ($calibrations as $index => $cal) {
                if ($cal->certificate_path && Storage::disk('local')->exists($cal->certificate_path)) {
                    $ext = pathinfo($cal->certificate_path, PATHINFO_EXTENSION);
                    $calName = "Certificados/Certificado_{$cal->calibration_date->format('Y-m-d')}_{$cal->id}.{$ext}";
                    $zip->addFile(Storage::disk('local')->path($cal->certificate_path), $calName);
                }
            }

            // 3. Adicionar Fotos e Anexos Gerais
            $attachments = $instrument->attachments;
            foreach ($attachments as $attachment) {
                if (Storage::disk('local')->exists($attachment->file_path)) {
                    $zip->addFile(Storage::disk('local')->path($attachment->file_path), 'Anexos/'.$attachment->file_name);
                }
            }

            // 4. Adicionar Relatório de Não Conformidades (Se houver)
            $ncs = $instrument->nonConformities;
            if ($ncs->count() > 0) {
                $ncSummary = $ncs->map(fn ($nc) => [
                    'id' => $nc->id,
                    'title' => $nc->title,
                    'status' => $nc->status,
                    'created_at' => $nc->created_at->toDateTimeString(),
                ])->toArray();
                $zip->addFromString('HISTORICO_NAO_CONFORMIDADES.json', json_encode($ncSummary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }

            $zip->close();
        }

        return $tempPath;
    }
}
