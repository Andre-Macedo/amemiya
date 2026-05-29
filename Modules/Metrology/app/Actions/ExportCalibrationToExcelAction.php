<?php

declare(strict_types=1);

namespace Modules\Metrology\Actions;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Metrology\Exports\Sheets\CalibrationCalculationSheet;
use Modules\Metrology\Exports\Sheets\CalibrationCoverSheet;
use Modules\Metrology\Exports\Sheets\CalibrationReadingsSheet;
use Modules\Metrology\Models\Calibration;

class ExportCalibrationToExcelAction implements WithMultipleSheets
{
    use Exportable;

    /**
     * Inicializa uma nova instância da Action.
     *
     * @param  Calibration  $calibration  A calibração a ser exportada.
     */
    public function __construct(
        protected Calibration $calibration
    ) {}

    /**
     * Define as planilhas que compõem o arquivo Excel.
     *
     * Retorna um array de objetos Sheet que serão exportados.
     *
     * @return array<int, mixed> Lista de planilhas.
     */
    public function sheets(): array
    {
        return [
            new CalibrationCoverSheet($this->calibration),
            new CalibrationReadingsSheet($this->calibration),
            new CalibrationCalculationSheet($this->calibration),
        ];
    }
}
