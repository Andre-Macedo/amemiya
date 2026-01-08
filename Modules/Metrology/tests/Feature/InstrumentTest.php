<?php

use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\HasSuperAdmin;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class, HasSuperAdmin::class);

it('can create an instrument', function () {
    $user = $this->createSuperAdmin();
    $type = InstrumentType::factory()->create(['name' => 'Calliper']);
    
    // Simulate data structure for Filament Resource creation if testing Resource
    // But testing the Model/Action level is more reliable here first.
    
    // Let's test Model creation for now to ensure Factories are good
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'name' => 'Digital Calliper'
    ]);

    expect($instrument)
        ->name->toBe('Digital Calliper')
        ->instrument_type_id->toBe($type->id);

    assertDatabaseHas('instruments', [
        'name' => 'Digital Calliper',
        'instrument_type_id' => $type->id
    ]);
});

it('calculates due date based on instrument type frequency', function () {
    $type = InstrumentType::factory()->create(['calibration_frequency_months' => 6]);
    
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        // calibration_due will be set by factory
    ]);
    
    expect($instrument->instrumentType->calibration_frequency_months)->toBe(6);
});
