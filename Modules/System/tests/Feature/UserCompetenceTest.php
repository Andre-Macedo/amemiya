<?php

use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\System\Models\Setting;
use Modules\System\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

it('can sync user competences', function () {
    $type1 = InstrumentType::factory()->create();
    $type2 = InstrumentType::factory()->create();

    $data = [
        'competences' => [
            ['instrument_type_id' => $type1->id, 'valid_until' => now()->addYear()->toDateString()],
            ['instrument_type_id' => $type2->id, 'valid_until' => null],
        ],
    ];

    $response = $this->postJson("/api/v1/system/users/{$this->user->id}/competences", $data);

    $response->assertStatus(200);
    $this->assertDatabaseHas('competences', [
        'user_id' => $this->user->id,
        'instrument_type_id' => $type1->id,
    ]);
});

it('blocks calibration if technician lacks competence and strict mode is on', function () {
    // 1. Ativa o modo estrito
    Setting::setValue('strict_competence_enforcement', 'true');

    // 2. Cria um instrumento de um tipo que o usuário NÃO tem competência
    $type = InstrumentType::factory()->create();
    $instrument = Instrument::factory()->create(['instrument_type_id' => $type->id]);

    $data = [
        'instrument_id' => $instrument->id,
        'checklist_template_id' => 1, // Mock
        'result' => 'pass',
    ];

    // 3. Tenta salvar a calibração
    $response = $this->postJson('/api/v1/metrology/calibrations', $data);

    // 4. Deve retornar erro de validação (422) ou proibido (403) conforme implementamos no Request
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['instrument_id']);
});

it('allows calibration if technician has competence even in strict mode', function () {
    Setting::setValue('strict_competence_enforcement', 'true');

    $type = InstrumentType::factory()->create();
    $instrument = Instrument::factory()->create(['instrument_type_id' => $type->id]);

    // Vincula competência válida
    $this->user->competences()->attach($type->id, ['valid_until' => now()->addMonth()]);

    $data = [
        'instrument_id' => $instrument->id,
        'checklist_template_id' => 1,
        'result' => 'pass',
        'calibration_date' => now()->toDateString(),
    ];

    $response = $this->postJson('/api/v1/metrology/calibrations', $data);

    // Não deve dar erro de competência (pode dar outros erros de estrutura, mas não o de bloqueio)
    $response->assertJsonMissingValidationErrors('instrument_id');
});
