<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ReferenceStandard;

class PrepareCertificateDataAction
{
    /**
     * Prepara os dados brutos da calibração para exibição no certificado.
     *
     * Processa os itens do checklist, calculando médias, erros e incertezas
     * para apresentar na tabela de resultados do certificado.
     * Também coleta informações sobre os padrões utilizados.
     *
     * @param  Calibration  $calibration  A calibração cujos dados serão processados.
     * @return array{
     *     results: array<int, array{
     *         step: string,
     *         nominal: float,
     *         readings: array<float>,
     *         average: float,
     *         error: float,
     *         uncertainty: float|mixed,
     *         k_factor: float,
     *         result: string|mixed
     *     }>,
     *     standards: array<int, ReferenceStandard>
     * } Dados estruturados para o certificado.
     */
    public function execute(Calibration $calibration): array
    {
        // Garante que os relacionamentos necessários foram carregados
        $calibration->load(['checklist.items.referenceStandard', 'calibratedItem', 'performedBy']);

        $results = [];
        $standards = collect();

        if ($calibration->checklist) {
            foreach ($calibration->checklist->items as $item) {
                // Coleta Padrões Únicos usados na calibração
                if ($item->referenceStandard) {
                    $standards->push($item->referenceStandard);
                }

                // Processa Itens Numéricos (Leituras)
                if ($item->question_type === 'numeric' && ! empty($item->readings)) {

                    // Lógica para extrair dados relevantes para a tabela do certificado
                    // Assume que readings é um array de ['value' => 10.01]
                    $readings = collect($item->readings)->pluck('value')->filter()->map(fn ($v) => (float) $v);

                    if ($readings->isEmpty()) {
                        continue;
                    }

                    $avg = $readings->avg();
                    // Obtém valor nominal do item ou tenta extrair do texto do passo
                    $nominal = (float) $item->nominal_value ?? (float) filter_var($item->step, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    $error = $avg - $nominal;

                    $results[] = [
                        'step' => $item->step,
                        'nominal' => $nominal,
                        'readings' => $readings->toArray(),
                        'average' => $avg,
                        'error' => $error,
                        'uncertainty' => $item->uncertainty ?? $calibration->uncertainty, // Usa incerteza global se a do item estiver ausente
                        'k_factor' => 2.00, // Padrão k=2
                        'result' => $item->result ?? ($calibration->result === CalibrationResult::Approved ? 'Approved' : 'Rejected'),
                    ];
                }
            }
        }

        return [
            'results' => $results,
            'standards' => $standards->unique('id')->values()->all(),
        ];
    }
}
