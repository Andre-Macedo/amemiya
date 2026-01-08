<?php

use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\CalibrationChecklist; // Assuming name or using relationship
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Actions\PrepareCertificateDataAction;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('prepares certificate data correctly', function () {
    // 1. Setup Data
    $instrument = Instrument::factory()->create();
    $standard = ReferenceStandard::factory()->create(['name' => 'Ref Std 1']);
    
    $calibration = Calibration::factory()->create([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'result' => 'approved',
        'uncertainty' => 0.005,
    ]);
    
    $checklist = Checklist::factory()->create(['calibration_id' => $calibration->id]);
    
    // Create Items with Readings
    ChecklistItem::factory()->create([
        'checklist_id' => $checklist->id,
        'step' => '10.00 mm',
        'nominal_value' => 10.00,
        'reference_standard_id' => $standard->id,
        'question_type' => 'numeric',
        'readings' => [['value' => 10.01], ['value' => 10.01]], // Avg 10.01 -> Error 0.01
    ]);
    
    // 2. Execute Action
    $action = new PrepareCertificateDataAction();
    $data = $action->execute($calibration);
    
    // 3. Assertions
    expect($data['results'])->toHaveCount(1);
    expect($data['results'][0]['nominal'])->toBe(10.00);
    expect($data['results'][0]['average'])->toBe(10.01);
    expect($data['results'][0]['error'])->toBe(0.01);
    expect($data['results'][0]['uncertainty'])->toBe(0.005); // Fallback to global
    
    expect($data['standards'])->toHaveCount(1);
    expect($data['standards'][0]->id)->toBe($standard->id);
});
