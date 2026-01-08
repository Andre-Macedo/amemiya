<?php

namespace Modules\Metrology\Tests\Feature;

use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\IntermediateCheck;
use Tests\Concerns\HasSuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Metrology\Filament\Clusters\Metrology\Resources\IntermediateChecks\Pages\CreateIntermediateCheck;

uses(RefreshDatabase::class, HasSuperAdmin::class);

test('it can create an intermediate check via filament form', function () {
    $user = $this->createSuperAdmin();
    
    $instrument = Instrument::factory()->create(['name' => 'Caliper Test']);

    Livewire::test(CreateIntermediateCheck::class)
        ->fillForm([
            'instrument_id' => $instrument->id,
            'check_date' => now()->format('Y-m-d'),
            'result' => 'passed',
            'performed_by' => $user->id,
            'temperature' => '20.5',
            'humidity' => '50',
            'notes' => 'Daily verification OK',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('intermediate_checks', [
        'instrument_id' => $instrument->id,
        'result' => 'passed',
        'notes' => 'Daily verification OK',
    ]);
});

test('intermediate check failure records correctly', function () {
    $user = $this->createSuperAdmin();
    $instrument = Instrument::factory()->create();

    $check = IntermediateCheck::create([
        'instrument_id' => $instrument->id,
        'check_date' => now(),
        'result' => 'failed',
        'performed_by' => $user->id,
        'notes' => 'Broken tip',
    ]);

    expect($check->result)->toBe('failed');
    // Note: Business logic might eventually trigger instrument status update, 
    // but for now we verify the record exists.
});
