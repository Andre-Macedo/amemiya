<?php

namespace Modules\Metrology\Tests\Feature;

use Modules\Metrology\Actions\CreateChecklistAction;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Services\UncertaintyCalculator;
use Tests\Concerns\HasSuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

// Use the trait to get createSuperAdmin helper
uses(RefreshDatabase::class, HasSuperAdmin::class);

beforeEach(function () {
    // Ensure modules are migrated if using SQLite memory
    Artisan::call('module:migrate', ['module' => 'Metrology']);

    // Create User with permissions
    $this->user = $this->createSuperAdmin();

    // Create Instrument Type
    $type = \Modules\Metrology\Models\InstrumentType::factory()->create([
        'calibration_frequency_months' => 12
    ]);

    // Create Instrument
    $this->instrument = Instrument::factory()->create([
        'name' => 'Paquímetro Digital',
        'instrument_type_id' => $type->id,
        'resolution' => 0.01,
        'mpe' => '0.03',
    ]);

    // Create Reference Standard
    $this->standard = ReferenceStandard::factory()->create([
        'name' => 'Bloco Padrão 10mm',
        'nominal_value' => 10.00,
        'actual_value' => 10.001,
        'uncertainty' => 0.002,
    ]);

    // Create Checklist Template
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
    // 1. Create Calibration
    $calibration = Calibration::create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $this->instrument->id,
        'calibration_date' => now(),
        'performed_by_id' => $this->user->id,
        'type' => 'internal',
        'type' => 'internal',
        // 'status' is not correct for Calibration, checking create parameters
    ]);

    // 2. Create Checklist (Action)
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
            ]
        ]
    ];

    (new CreateChecklistAction())->execute($calibration, $checklistData);

    $this->assertDatabaseHas('checklists', ['calibration_id' => $calibration->id]);

    // 3. Simulate Calculation (GUM)
    $calculator = new UncertaintyCalculator();
    $readings = [10.01, 10.02, 10.01];
    
    $result = $calculator->calculate(
        $readings,
        $this->instrument->resolution, 
        $this->standard->actual_value, 
        $this->standard->uncertainty   
    );

    // 4. Update Calibration -> Should Trigger Listener -> ProcessCalibrationAction
    $calibration->update([
        'deviation' => $result['bias'],
        'uncertainty' => $result['expanded_uncertainty'],
        'result' => \Modules\Metrology\Enums\CalibrationResult::Approved, // Initial intent
    ]);

    // 5. Verify Final State
    $this->instrument->refresh();
    $calibration->refresh();

    expect($this->instrument->status)->toBe(\Modules\Metrology\Enums\ItemStatus::Active);
    expect($calibration->result)->toBe(\Modules\Metrology\Enums\CalibrationResult::Approved);
    
    // Deviation 0.0123 < MPE 0.03 -> Approved
    expect($calibration->deviation)->toEqualWithDelta(0.0123, 0.001);
});

test('full calibration lifecycle rejected via event listener', function () {
    $calibration = Calibration::create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $this->instrument->id,
        'calibration_date' => now(),
        'performed_by_id' => $this->user->id,
        'type' => 'internal',
        'type' => 'internal',
    ]);

    // Simulate bad result
    $calibration->update([
        'deviation' => 0.05, // > MPE 0.03
        'uncertainty' => 0.005,
        'result' => \Modules\Metrology\Enums\CalibrationResult::Approved, // Try to approve
    ]);

    // Listener should have flipped it to rejected
    $calibration->refresh();
    $this->instrument->refresh();

    expect($calibration->result)->toBe(\Modules\Metrology\Enums\CalibrationResult::Rejected);
    expect($this->instrument->status)->toBe(\Modules\Metrology\Enums\ItemStatus::Rejected);
});
