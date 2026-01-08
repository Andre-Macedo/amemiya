<?php

use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\ReferenceStandardType;
use Modules\Metrology\Models\Calibration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\HasSuperAdmin;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class, HasSuperAdmin::class);

beforeEach(function () {
    \Illuminate\Support\Facades\Artisan::call('module:migrate', ['module' => 'Metrology']);
    $this->createSuperAdmin();
});

it('can create a reference standard', function () {
    $this->createSuperAdmin();
    $type = ReferenceStandardType::factory()->create(['name' => 'Gauge Block']);
    
    $standard = ReferenceStandard::factory()->create([
        'reference_standard_type_id' => $type->id,
        'name' => 'Master Gauge Block Set'
    ]);

    expect($standard)
        ->name->toBe('Master Gauge Block Set')
        ->reference_standard_type_id->toBe($type->id);

    assertDatabaseHas('reference_standards', [
        'name' => 'Master Gauge Block Set',
        'reference_standard_type_id' => $type->id
    ]);
});

it('calculates next calibration due date based on type frequency', function () {
    // 1. Setup Type with 24 months frequency
    $type = ReferenceStandardType::factory()->create(['calibration_frequency_months' => 24]);
    $standard = ReferenceStandard::factory()->create(['reference_standard_type_id' => $type->id]);
    
    // 2. Create a calibration
    $calibrationDate = now()->subMonths(10);
    $calibration = Calibration::factory()->create([
        'calibrated_item_type' => ReferenceStandard::class,
        'calibrated_item_id' => $standard->id,
        'calibration_date' => $calibrationDate,
        'result' => 'approved' 
    ]);
    
    // 3. Verify getNextCalibrationDueAttribute logic
    // Logic: latestCalibration.date + type.frequency
    $expectedDate = $calibrationDate->copy()->addMonths(24)->startOfDay();
    
    expect($standard->next_calibration_due?->startOfDay()->equalTo($expectedDate))->toBeTrue();
});

it('resolves effective serial number from parent for kits', function () {
    $parent = ReferenceStandard::factory()->create(['serial_number' => 'KIT-123']);
    $child = ReferenceStandard::factory()->create([
        'parent_id' => $parent->id,
        'serial_number' => null // Child has no serial, should inherit
    ]);

    expect($child->effective_serial_number)->toBe('KIT-123 (Kit)');
});
