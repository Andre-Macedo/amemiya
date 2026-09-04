<?php

namespace Modules\Metrology\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\Metrology\Actions\MergeCertificateAttachmentsAction;
use Modules\Metrology\Actions\PrepareCertificateDataAction;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\System\Models\User;
use Tests\TestCase;
use Webklex\PDFMerger\Facades\PDFMergerFacade;

class CertificateMergingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_merge_certificate_with_attachments()
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. Cria Instrumento
        $instrument = Instrument::factory()->create();

        // 2. Cria Padrão de Referência
        $standard = ReferenceStandard::factory()->create(['status' => 'active']);

        // 3. Cria Calibração usando este Padrão (via Checklist)
        $calibration = Calibration::factory()->create([
            'calibrated_item_type' => Instrument::class,
            'calibrated_item_id' => $instrument->id,
            'result' => CalibrationResult::Approved,
            'status' => 'published',
        ]);

        $checklist = Checklist::factory()->create(['calibration_id' => $calibration->id]);
        ChecklistItem::factory()->create([
            'checklist_id' => $checklist->id,
            'reference_standard_id' => $standard->id,
            'readings' => [10.00],
        ]);

        // Mock PDFMerger
        $mockMerger = \Mockery::mock();
        $mockMerger->shouldReceive('addString')->once();
        $mockMerger->shouldReceive('addPDF')->with(\Mockery::on(function ($arg) {
            return str_contains($arg, 'standard.pdf');
        }), 'all')->once();
        $mockMerger->shouldReceive('merge')->once();
        $mockMerger->shouldReceive('save')->once()->andReturn('fake-merged-pdf-content');

        PDFMergerFacade::shouldReceive('init')->once()->andReturn($mockMerger);

        // Mock PrepareCertificateDataAction
        $mockStandard = new class
        {
            public $name = 'Standard Padrão';

            public $serial_number = 'STD-001';

            public $active_certificate_url = 'certificates/standard.pdf';
        };

        $this->mock(PrepareCertificateDataAction::class, function ($mock) use ($mockStandard) {
            $mock->shouldReceive('execute')
                ->withAnyArgs()
                ->andReturn([
                    'results' => [],
                    'standards' => [$mockStandard],
                ]);
        });

        // Garante que o arquivo exista para a verificação
        Storage::disk('public')->put('certificates/standard.pdf', 'dummy content');

        // Act
        $action = new MergeCertificateAttachmentsAction;
        $mergedPdf = $action->execute($calibration);

        // Assert
        $this->assertEquals('fake-merged-pdf-content', $mergedPdf);
    }
}
