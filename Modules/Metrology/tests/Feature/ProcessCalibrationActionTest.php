<?php

namespace Modules\Metrology\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\Metrology\Notifications\CalibrationRejectedNotification;
use Modules\System\Models\User;
use Tests\Concerns\HasSuperAdmin;

uses(RefreshDatabase::class, HasSuperAdmin::class);

test('it automatically opens an NC when calibration is rejected', function () {
    Notification::fake();
    $this->createSuperAdmin(['id' => 1]);

    $instrument = Instrument::factory()->create(['mpe' => '0.05']);

    // Create a published calibration that is rejected
    $calibration = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'nominal_value' => '10.00',
        'actual_value' => '10.10', // 0.10 > 0.05
        'status' => 'published',
        'performed_by_id' => 1,
    ]);

    // Check if NC was created
    $this->assertDatabaseHas('non_conformities', [
        'calibration_id' => $calibration->id,
        'item_id' => $instrument->id,
        'status' => 'open',
    ]);

    // Check if Notification was sent to admins
    Notification::assertSentTo(User::all(), CalibrationRejectedNotification::class);
});

test('it approves calibration within tolerance (simple rule)', function () {
    $this->createSuperAdmin(['id' => 1]);

    $type = InstrumentType::factory()->create(['decision_rule' => 'simple']);
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'mpe' => '0.05', // 0.05 mm
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
        ->and($calibration->result)->toBe(CalibrationResult::Approved);

    $instrument->refresh();
    expect($instrument->status)->toBe(ItemStatus::Active);
});

test('it rejects calibration outside tolerance (simple rule)', function () {
    $this->createSuperAdmin(['id' => 1]);

    $type = InstrumentType::factory()->create(['decision_rule' => 'simple']);
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'mpe' => '0.05',
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

    expect($calibration->result)->toBe(CalibrationResult::Rejected);

    $instrument->refresh();
    expect($instrument->status)->toBe(ItemStatus::Rejected);
});

test('it rejects calibration with uncertainty accounted (decision rule)', function () {
    $this->createSuperAdmin(['id' => 1]);

    // Rule: Uncertainty Accounted (Error + U < MPE)
    $type = InstrumentType::factory()->create(['decision_rule' => 'uncertainty_accounted']);
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'mpe' => '0.05',
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

    expect($calibration->result)->toBe(CalibrationResult::Rejected);
});

test('it handles guard band conditional approval', function () {
    $this->createSuperAdmin(['id' => 1]);

    // Rule: Guard Band (Error <= MPE - U)
    $type = InstrumentType::factory()->create(['decision_rule' => 'guard_band']);
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'mpe' => '0.05',
    ]);

    // Scenario A: Full Approval
    // Error: 0.02. Uncertainty: 0.02. Limit: 0.05.
    // Reduced Limit: 0.05 - 0.02 = 0.03.
    // 0.02 <= 0.03 -> Approved
    $calibrationA = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'nominal_value' => '10.00',
        'actual_value' => '10.02',
        'uncertainty' => '0.02',
        'performed_by_id' => 1,
    ]);
    expect($calibrationA->refresh()->result)->toBe(CalibrationResult::Approved);

    // Scenario B: Conditional Approval (Doubt Zone)
    // Error: 0.04. Uncertainty: 0.02. Limit: 0.05.
    // 0.04 > (0.05 - 0.02) AND 0.04 <= 0.05 -> Conditional
    $calibrationB = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'nominal_value' => '10.00',
        'actual_value' => '10.04',
        'uncertainty' => '0.02',
        'performed_by_id' => 1,
    ]);
    expect($calibrationB->refresh()->result)->toBe(CalibrationResult::Conditional);

    // Scenario C: Rejection
    // Error: 0.06. Uncertainty: 0.02. Limit: 0.05.
    // 0.06 > 0.05 -> Rejected
    $calibrationC = Calibration::factory()->create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'nominal_value' => '10.00',
        'actual_value' => '10.06',
        'uncertainty' => '0.02',
        'performed_by_id' => 1,
    ]);
    expect($calibrationC->refresh()->result)->toBe(CalibrationResult::Rejected);
});
