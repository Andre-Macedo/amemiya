<?php

declare(strict_types=1);

namespace Modules\Metrology\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Metrology\Models\Calibration;

class CalibrationReadingsSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        protected Calibration $calibration
    ) {}

    /**
     * Gera a coleção de dados com as leituras individuais de cada ponto.
     *
     * @return Collection
     */
    public function collection()
    {
        $rows = collect();

        if ($this->calibration->checklist) {
            foreach ($this->calibration->checklist->items as $item) {
                if ($item->question_type === 'numeric' && ! empty($item->readings)) {

                    $readings = collect($item->readings)->pluck('value')->map(fn ($v) => (float) $v);
                    $avg = $readings->avg();
                    $nominal = (float) ($item->nominal_value ?? $item->step); // Tenta usar valor nominal ou passo
                    $error = $avg - $nominal;

                    $rows->push([
                        'step' => $item->step,
                        'nominal' => $nominal,
                        'readings' => $readings->implode(', '),
                        'average' => $avg,
                        'error' => $error,
                        'uncertainty' => $item->uncertainty, // Incerteza individual se houver
                        'result' => $item->result,
                    ]);
                }
            }
        }

        return $rows;
    }

    /**
     * Cabeçalhos da planilha.
     */
    public function headings(): array
    {
        return [
            'Ponto (Step)',
            'Valor Nominal',
            'Leituras Individuais',
            'Média',
            'Erro (Tendência)',
            'Incerteza do Ponto',
            'Resultado',
        ];
    }

    /**
     * Título da aba.
     */
    public function title(): string
    {
        return 'Medições';
    }
}
