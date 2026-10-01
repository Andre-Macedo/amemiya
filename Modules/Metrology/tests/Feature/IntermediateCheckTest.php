<?php

namespace Modules\Metrology\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\IntermediateCheck;
use Tests\Concerns\HasSuperAdmin;

uses(RefreshDatabase::class, HasSuperAdmin::class);

test('it can create an intermediate check', function () {
    $user = $this->createSuperAdmin();

    $instrument = Instrument::factory()->create(['name' => 'Caliper Test']);

    $check = IntermediateCheck::create([
        'instrument_id' => $instrument->id,
        'check_date' => now()->format('Y-m-d'),
        'result' => 'passed',
        'performed_by' => $user->id,
        'temperature' => 20.5,
        'humidity' => 50,
        'notes' => 'Daily verification OK',
    ]);

    $this->assertDatabaseHas('intermediate_checks', [
        'instrument_id' => $instrument->id,
        'result' => 'passed',
        'notes' => 'Daily verification OK',
    ]);
});

test('intermediate check failure automatically blocks instrument and creates non-conformity', function () {
    $user = $this->createSuperAdmin();
    $instrument = Instrument::factory()->create([
        'status' => ItemStatus::Active,
    ]);

    $check = IntermediateCheck::create([
        'instrument_id' => $instrument->id,
        'check_date' => now(),
        'result' => 'failed',
        'performed_by' => $user->id,
        'notes' => 'Pontas de medição danificadas, desvio superior à tolerância',
    ]);

    expect($check->result)->toBe('failed');

    // Instrumento deve ser bloqueado com status Rejected (ISO 17025 §6.4.10)
    expect($instrument->fresh()->status)->toBe(ItemStatus::Rejected);

    // Não conformidade deve ser aberta automaticamente
    $this->assertDatabaseHas('non_conformities', [
        'item_type' => Instrument::class,
        'item_id' => $instrument->id,
        'status' => 'open',
        'priority' => 'high',
    ]);
});
