<?php

declare(strict_types=1);

namespace Modules\Metrology\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Modules\Metrology\Models\Calibration;

class CalibrationCoverSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        protected Calibration $calibration
    ) {}

    /**
     * Gera os dados da capa do certificado em formato de array.
     */
    public function array(): array
    {
        $item = $this->calibration->calibratedItem;

        return [
            ['Certificado:', $this->calibration->certificate_code ?? 'N/A'],
            ['Data Calibração:', $this->calibration->calibration_date->format('d/m/Y')],
            ['Status:', $this->calibration->status],
            ['Resultado:', $this->calibration->result->getLabel()],
            [''],
            ['--- IDENTIFICAÇÃO DO ITEM ---'],
            ['Nome:', $item->name ?? 'N/A'],
            ['Fabricante:', $item->manufacturer ?? 'N/A'],
            ['Modelo:', $item->model ?? 'N/A'],
            ['Serial:', $item->serial_number ?? 'N/A'],
            ['Identificação (Patrimônio):', $item->asset_number ?? 'N/A'],
            [''],
            ['--- AMBIENTE ---'],
            ['Temperatura:', ($this->calibration->temperature ?? '-').' °C'],
            ['Umidade:', ($this->calibration->humidity ?? '-').' %'],
            [''],
            ['--- RESULTADO FINAL ---'],
            ['Erro Máximo (Tendência):', $this->calibration->deviation ?? '-'],
            ['Incerteza Expandida (U):', $this->calibration->uncertainty ?? '-'],
            ['Fator k:', $this->calibration->k_factor ?? '-'],
        ];
    }

    /**
     * Cabeçalhos da planilha (colunas Campo e Valor).
     */
    public function headings(): array
    {
        return ['CAMPO', 'VALOR'];
    }

    /**
     * Título da aba.
     */
    public function title(): string
    {
        return 'Capa';
    }
}
