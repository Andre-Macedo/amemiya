<?php

namespace Modules\Metrology\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Metrology\Actions\ExportCalibrationToExcelAction;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\User;
use Tests\TestCase;

class CalibrationExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_export_calibration_to_excel()
    {
        Excel::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. Configura Dados com Cálculo
        $instrument = Instrument::factory()->create(['name' => 'Paquímetro Digital']);

        $calibration = Calibration::factory()->create([
            'calibrated_item_type' => Instrument::class,
            'calibrated_item_id' => $instrument->id,
            'status' => 'published',
            'certificate_code' => 'CERT-2025-001',
            'calculation_data' => [
                [
                    'source' => 'Resolution',
                    'standard_uncertainty' => 0.005,
                    'type' => 'B',
                    'description' => 'Resolução do Instrumento',
                ],
            ],
        ]);

        $checklist = Checklist::factory()->create(['calibration_id' => $calibration->id]);
        ChecklistItem::factory()->create([
            'checklist_id' => $checklist->id,
            'step' => '10 mm',
            'question_type' => 'numeric',
            'readings' => [['value' => 10.01], ['value' => 10.02]],
            'nominal_value' => 10.00,
        ]);

        // 2. Executa Exportação
        $action = new ExportCalibrationToExcelAction($calibration);
        $action->download('test.xlsx');

        // 3. Validação (Assert)
        Excel::assertDownloaded('test.xlsx', function (ExportCalibrationToExcelAction $export) {
            // Verifica se as abas estão presentes
            $sheets = $export->sheets();

            return count($sheets) === 3;
        });
    }
}
