<?php

namespace Modules\Metrology\Tests\Feature;

use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Tests\Concerns\HasSuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class, HasSuperAdmin::class);

test('it approves calibration within tolerance (simple rule)', function () {
    $this->createSuperAdmin(['id' => 1]); 
    
    $type = InstrumentType::factory()->create(['decision_rule' => 'simple']);
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'mpe' => '0.05' // 0.05 mm
    ]);

    // Nominal: 10.00, Actual: 10.03 -> Deviation: 0.03.
    // 0.03 < 0.05 -> Approved
    $calibration = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'nominal_value' => '10.00',
        'actual_value' => '10.03',
        'uncertainty' => '0.01',
        'calibration_date' => now(),
        'performed_by_id' => 1,
    ]);

    $calibration->refresh();
    
    expect($calibration->deviation)->toEqual(0.03)
        ->and($calibration->result)->toBe('approved');

    $instrument->refresh();
    expect($instrument->status)->toBe('active');
});

test('it rejects calibration outside tolerance (simple rule)', function () {
    $this->createSuperAdmin(['id' => 1]);

    $type = InstrumentType::factory()->create(['decision_rule' => 'simple']);
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'mpe' => '0.05'
    ]);

    // Nominal: 10.00, Actual: 10.06 -> Deviation: 0.06.
    // 0.06 > 0.05 -> Rejected
    $calibration = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'nominal_value' => '10.00',
        'actual_value' => '10.06',
        'performed_by_id' => 1,
    ]);

    $calibration->refresh();

    expect($calibration->result)->toBe('rejected');
    
    $instrument->refresh();
    expect($instrument->status)->toBe('rejected');
});

test('it rejects calibration with uncertainty accounted (decision rule)', function () {
    $this->createSuperAdmin(['id' => 1]);
    
    // Rule: Uncertainty Accounted (Error + U < MPE)
    $type = InstrumentType::factory()->create(['decision_rule' => 'uncertainty_accounted']);
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'mpe' => '0.05'
    ]);

    // Error: 0.04. Uncertainty: 0.02. Total: 0.06 > 0.05 -> Reject
    $calibration = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'nominal_value' => '10.00',
        'actual_value' => '10.04',
        'uncertainty' => '0.02',
        'performed_by_id' => 1,
    ]);

    $calibration->refresh();
    
    expect($calibration->result)->toBe('rejected');
});
