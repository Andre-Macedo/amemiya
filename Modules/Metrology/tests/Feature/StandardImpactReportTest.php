<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Actions\GenerateStandardImpactReportAction;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\InstrumentCriticality;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Tests\Concerns\HasSuperAdmin;

uses(RefreshDatabase::class, HasSuperAdmin::class);

beforeEach(function (): void {
    Artisan::call('module:migrate', ['module' => 'Metrology']);
    $user = $this->createSuperAdmin();
    Sanctum::actingAs($user);
});

test('it builds impact analysis data and classifies metrological risk correctly', function (): void {
    $standard = ReferenceStandard::factory()->create([
        'name' => 'Bloco Padrão Classe 0',
        'uncertainty' => 0.002,
    ]);

    // Instrumento 1: Crítico (deve ser classificado como CRITICAL / Recall Imediato)
    $criticalInstrument = Instrument::factory()->create([
        'name' => 'Micrômetro Crítico de Segurança',
        'criticality' => InstrumentCriticality::SafetyNr12,
        'mpe_value' => 0.010,
    ]);

    // Instrumento 2: Não-crítico com TUR alto (mpe = 0.050 / u = 0.005 => TUR = 10 => LOW)
    $standardInstrument = Instrument::factory()->create([
        'name' => 'Paquímetro Operacional',
        'criticality' => InstrumentCriticality::OperationalReference,
        'mpe_value' => 0.050,
    ]);

    // Calibração 1 para instrumento crítico
    $cal1 = Calibration::factory()->create([
        'calibrated_item_id' => $criticalInstrument->id,
        'calibrated_item_type' => Instrument::class,
        'calibration_date' => now()->subDays(10),
        'status' => 'approved',
        'uncertainty' => 0.002,
        'result' => CalibrationResult::Approved,
    ]);
    $chk1 = Checklist::factory()->create(['calibration_id' => $cal1->id]);
    ChecklistItem::factory()->create([
        'checklist_id' => $chk1->id,
        'reference_standard_id' => $standard->id,
    ]);

    // Calibração 2 para instrumento operacional
    $cal2 = Calibration::factory()->create([
        'calibrated_item_id' => $standardInstrument->id,
        'calibrated_item_type' => Instrument::class,
        'calibration_date' => now()->subDays(5),
        'status' => 'approved',
        'uncertainty' => 0.005,
        'result' => CalibrationResult::Approved,
    ]);
    $chk2 = Checklist::factory()->create(['calibration_id' => $cal2->id]);
    ChecklistItem::factory()->create([
        'checklist_id' => $chk2->id,
        'reference_standard_id' => $standard->id,
    ]);

    $action = app(GenerateStandardImpactReportAction::class);
    $reportData = $action->buildReportData($standard);

    expect($reportData['stats']['total_calibrations'])->toBe(2)
        ->and($reportData['stats']['unique_instruments'])->toBe(2)
        ->and($reportData['stats']['critical_count'])->toBe(1)
        ->and($reportData['stats']['low_count'])->toBe(1)
        ->and($reportData['documentHash'])->not->toBeEmpty();
});

test('it serves standard impact analysis json via api', function (): void {
    $standard = ReferenceStandard::factory()->create();

    $response = $this->getJson("/api/v1/standards/{$standard->id}/impact-analysis");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'stats' => ['total_calibrations', 'unique_instruments', 'critical_count', 'moderate_count', 'low_count'],
            'meta' => ['total'],
            'report_code',
            'document_hash',
        ]);
});

test('it generates and streams pdf report for standard impact analysis', function (): void {
    $standard = ReferenceStandard::factory()->create([
        'name' => 'Termohigrômetro Padrão',
    ]);

    $instrument = Instrument::factory()->create();

    $cal = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'calibration_date' => now()->subDays(2),
        'status' => 'approved',
    ]);
    $chk = Checklist::factory()->create(['calibration_id' => $cal->id]);
    ChecklistItem::factory()->create([
        'checklist_id' => $chk->id,
        'reference_standard_id' => $standard->id,
    ]);

    $response = $this->get("/api/v1/standards/{$standard->id}/impact-analysis/pdf");

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'application/pdf');

    // Verifica que o conteúdo inicia com o magic header do formato PDF (%PDF-)
    expect(str_starts_with($response->getContent(), '%PDF-'))->toBeTrue();
});
