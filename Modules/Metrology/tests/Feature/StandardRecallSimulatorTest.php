<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\InstrumentCriticality;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\StandardRecallSimulatorService;
use Modules\System\Models\User;
use Tests\TestCase;

class StandardRecallSimulatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    private ReferenceStandard $standard;

    private Instrument $instrument;

    private Calibration $calibration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Laboratório Metrológico Alfa',
            'slug' => 'lab-alfa',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->standard = ReferenceStandard::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Bloco Padrão 50 mm Grau 0',
            'stock_number' => 'PAD-BL-50',
            'serial_number' => 'BP-99881',
            'nominal_value' => 50.0,
            'unit' => 'mm',
            'uncertainty' => 0.0005,
            'status' => ItemStatus::Active,
            'last_calibration_date' => now()->subMonths(6),
            'calibration_due' => now()->addMonths(6),
        ]);

        $this->instrument = Instrument::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Micrômetro Externo 25-50 mm',
            'stock_number' => 'MIC-001',
            'serial_number' => 'SN-MIC-01',
            'mpe' => 0.004, // MPE = ±4 µm
            'mpe_value' => 0.004,
            'mpe_type' => 'numeric',
            'resolution' => 0.001,
            'status' => ItemStatus::Active,
            'criticality' => InstrumentCriticality::OperationalReference,
            'acquisition_date' => now()->subYears(2),
        ]);

        // Calibração original: Erro = +0.003 mm (Dentro do MPE de 0.004 -> Pass)
        $this->calibration = Calibration::create([
            'tenant_id' => $this->tenant->id,
            'calibrated_item_id' => $this->instrument->id,
            'calibrated_item_type' => Instrument::class,
            'calibration_date' => now()->subMonths(2),
            'status' => 'published',
            'result' => CalibrationResult::Approved,
            'deviation' => 0.003,
            'uncertainty' => 0.001,
            'performed_by_id' => $this->user->id,
        ]);

        $template = ChecklistTemplate::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Procedimento de Micrômetro',
        ]);

        $checklist = Checklist::create([
            'tenant_id' => $this->tenant->id,
            'checklist_template_id' => $template->id,
            'calibration_id' => $this->calibration->id,
            'performed_by_id' => $this->user->id,
        ]);

        ChecklistItem::create([
            'checklist_id' => $checklist->id,
            'step' => 'Ponto 50 mm',
            'nominal_value' => 50.0,
            'reference_standard_id' => $this->standard->id,
            'readings' => [
                ['value' => '50.003'],
                ['value' => '50.003'],
            ],
            'question_type' => 'numeric',
            'order' => 1,
        ]);
    }

    public function test_simulator_detects_invalidated_calibration_when_drift_exceeds_mpe(): void
    {
        $service = app(StandardRecallSimulatorService::class);

        // Deriva de +0.002 mm adicionada a +0.003 resulta em +0.005 mm (> MPE de 0.004 mm)
        $result = $service->simulate(
            standard: $this->standard,
            driftMagnitude: 0.002,
            driftDirection: 'worst_case',
            startDate: now()->subMonths(4)->format('Y-m-d'),
            endDate: now()->format('Y-m-d')
        );

        $this->assertSame(1, $result['summary']['total_calibrations']);
        $this->assertSame(1, $result['summary']['invalidated_count']);
        $this->assertSame('CRITICAL', $result['summary']['severity_level']);
        $this->assertTrue($result['items'][0]['is_invalidated']);
        $this->assertSame(0.005, $result['items'][0]['simulated_deviation']);
    }

    public function test_simulator_keeps_conforming_when_drift_is_within_tolerance(): void
    {
        $service = app(StandardRecallSimulatorService::class);

        // Deriva minúscula de +0.0005 mm: +0.003 + 0.0005 = 0.0035 <= 0.004 mm (Conforme!)
        $result = $service->simulate(
            standard: $this->standard,
            driftMagnitude: 0.0005,
            driftDirection: 'worst_case',
            startDate: now()->subMonths(4)->format('Y-m-d'),
            endDate: now()->format('Y-m-d')
        );

        $this->assertSame(1, $result['summary']['total_calibrations']);
        $this->assertSame(0, $result['summary']['invalidated_count']);
        $this->assertFalse($result['items'][0]['is_invalidated']);
    }

    public function test_simulate_recall_via_api(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeader('X-Tenant-ID', (string) $this->tenant->id)
            ->postJson("/api/v1/standards/{$this->standard->id}/simulate-recall", [
                'drift_magnitude' => 0.002,
                'drift_direction' => 'worst_case',
                'start_date' => now()->subMonths(3)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]);

        $response->assertOk()
            ->assertJsonPath('summary.total_calibrations', 1)
            ->assertJsonPath('summary.invalidated_count', 1)
            ->assertJsonPath('summary.severity_level', 'CRITICAL');
    }
}
