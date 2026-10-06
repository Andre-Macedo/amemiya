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
        $calibration->load([
            'checklist.items.referenceStandard.parent',
            'checklist.checklistTemplate',
            'calibratedItem',
            'performedBy',
        ]);

        $results = [];
        $standards = collect();

        if ($calibration->checklist) {
            foreach ($calibration->checklist->items as $item) {
                // Coleta Padrões Únicos usados na calibração (incluindo o conjunto/kit se for parte de um)
                if ($item->referenceStandard) {
                    $std = $item->referenceStandard;
                    $standards->push($std);
                    if ($std->parent) {
                        $standards->push($std->parent);
                    }
                }

                // Processa Itens Numéricos (Leituras)
                if ($item->question_type === 'numeric' && ! empty($item->readings)) {

                    // Lógica para extrair dados relevantes para a tabela do certificado
                    // Suporta tanto array escalar [25.004, 25.0, 25.002] quanto array de objetos [['value' => 25.004]]
                    $rawReadings = collect($item->readings);
                    $first = $rawReadings->first();

                    if (is_array($first) && isset($first['value'])) {
                        $readings = $rawReadings->pluck('value')->filter(fn ($v) => $v !== null && $v !== '')->map(fn ($v) => (float) $v);
                    } else {
                        $readings = $rawReadings->filter(fn ($v) => $v !== null && $v !== '' && is_numeric($v))->map(fn ($v) => (float) $v);
                    }

                    if ($readings->isEmpty()) {
                        continue;
                    }

                    $avg = round((float) $readings->avg(), 6);
                    // Obtém valor nominal do item ou tenta extrair do texto do passo
                    $nominal = (float) ($item->nominal_value ?? filter_var($item->step, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                    $error = round($avg - $nominal, 8);

                    $results[] = [
                        'step' => $item->step,
                        'nominal' => $nominal,
                        'readings' => $readings->toArray(),
                        'average' => $avg,
                        'error' => $error,
                        'uncertainty' => $item->uncertainty ?? $calibration->uncertainty, // Usa incerteza global se a do item estiver ausente
                        'k_factor' => $item->k_factor ?? 2.00, // Fator de abrangência do item ou padrão k=2
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
