<?php

namespace Modules\Metrology\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Metrology\Actions\CreateChecklistAction;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\UncertaintyCalculator;
use Tests\Concerns\HasSuperAdmin;

// Use a trait para obter o helper createSuperAdmin
uses(RefreshDatabase::class, HasSuperAdmin::class);

beforeEach(function () {
    // Garante que os módulos sejam migrados se estiver usando SQLite em memória
    Artisan::call('module:migrate', ['module' => 'Metrology']);

    // Cria Usuário com permissões
    $this->user = $this->createSuperAdmin();

    // Cria Tipo de Instrumento
    $type = InstrumentType::factory()->create([
        'calibration_frequency_months' => 12,
    ]);

    // Cria Instrumento
    $this->instrument = Instrument::factory()->create([
        'name' => 'Paquímetro Digital',
        'instrument_type_id' => $type->id,
        'resolution' => 0.01,
        'mpe' => '0.03',
    ]);

    // Cria Padrão de Referência
    $this->standard = ReferenceStandard::factory()->create([
        'name' => 'Bloco Padrão 10mm',
        'nominal_value' => 10.00,
        'actual_value' => 10.001,
        'uncertainty' => 0.002,
    ]);

    // Cria Modelo de Checklist
    $this->template = ChecklistTemplate::create([
        'name' => 'Procedimento Paquímetro',
        'instrument_type_id' => $this->instrument->instrument_type_id,
    ]);

    $this->template->items()->create([
        'step' => 'Medição 10mm',
        'question_type' => 'numeric',
        'required_readings' => 3,
        'nominal_value' => 10.00,
        'order' => 1,
    ]);
});

test('full calibration lifecycle approved via event listener', function () {
    // 1. Cria Calibração
    $calibration = Calibration::create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $this->instrument->id,
        'calibration_date' => now(),
        'performed_by_id' => $this->user->id,
        'type' => 'internal',
        'type' => 'internal',
        // 'status' não é correto para parâmetros de create da Calibração
    ]);

    // 2. Cria Checklist (Action)
    $checklistData = [
        'template_id' => $this->template->id,
        'items' => [
            [
                'step' => 'Medição 10mm',
                'question_type' => 'numeric',
                'order' => 1,
                'required_readings' => 3,
                'reference_standard_id' => $this->standard->id,
                'readings' => [
                    ['value' => 10.01],
                    ['value' => 10.02],
                    ['value' => 10.01],
                ],
            ],
        ],
    ];

    (new CreateChecklistAction)->execute($calibration, $checklistData);

    $this->assertDatabaseHas('checklists', ['calibration_id' => $calibration->id]);

    // 3. Simula Cálculo (GUM)
    $calculator = new UncertaintyCalculator;
    $readings = [10.01, 10.02, 10.01];

    $result = $calculator->calculate(
        $readings,
        $this->instrument->resolution,
        $this->standard->actual_value,
        $this->standard->uncertainty
    );

    // 4. Atualiza Calibração -> Deve Disparar Listener -> ProcessCalibrationAction
    $calibration->update([
        'status' => 'published',
        'deviation' => $result['bias'],
        'uncertainty' => $result['expanded_uncertainty'],
        'result' => CalibrationResult::Approved, // Intenção inicial
    ]);

    // 5. Verifica Estado Final
    $this->instrument->refresh();
    $calibration->refresh();

    expect($this->instrument->status)->toBe(ItemStatus::Active);
    expect($calibration->result)->toBe(CalibrationResult::Approved);

    // Desvio 0.0123 < MPE 0.03 -> Aprovado
    expect($calibration->deviation)->toEqualWithDelta(0.0123, 0.001);
});

test('full calibration lifecycle rejected via event listener', function () {
    $calibration = Calibration::create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $this->instrument->id,
        'calibration_date' => now(),
        'performed_by_id' => $this->user->id,
        'type' => 'internal',
    ]);

    // Simula resultado ruim
    $calibration->update([
        'status' => 'published',
        'deviation' => 0.05, // > MPE 0.03
        'uncertainty' => 0.005,
        'result' => CalibrationResult::Approved, // Tenta aprovar
    ]);

    // Listener deve ter alterado para rejeitado

    $calibration->refresh();
    $this->instrument->refresh();

    expect($calibration->result)->toBe(CalibrationResult::Rejected);
    expect($this->instrument->status)->toBe(ItemStatus::Rejected);
});
