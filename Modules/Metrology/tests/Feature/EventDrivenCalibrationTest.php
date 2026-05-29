<?php

use Illuminate\Support\Facades\Artisan;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\Metrology\Models\ReferenceStandard;
use Tests\Concerns\HasSuperAdmin;

uses(HasSuperAdmin::class);

beforeEach(function () {
    Artisan::call('module:migrate', ['module' => 'Metrology']);
    $this->user = $this->createSuperAdmin();
});

it('creates checklist via listener when input provided', function () {
    $type = InstrumentType::factory()->create();
    $instrument = Instrument::factory()->create(['instrument_type_id' => $type->id]);
    $template = ChecklistTemplate::create(['name' => 'Tpl', 'instrument_type_id' => $type->id]);

    $template->items()->create([
        'step' => 'Step 1',
        'question_type' => 'numeric',
        'order' => 1,
    ]);

    $checklistData = [
        'template_id' => $template->id,
        'items' => [
            [
                'step' => 'Step 1',
                'question_type' => 'numeric',
                'order' => 1,
            ],
        ],
    ];

    $calibration = new Calibration;
    $calibration->fill([
        'calibrated_item_id' => $instrument->id,
        'calibrated_item_type' => Instrument::class,
        'calibration_date' => now(),
        'performed_by_id' => $this->user->id,
        'type' => 'internal',
        'result' => CalibrationResult::Approved,
    ]);

    $calibration->checklistInput = $checklistData;
    $calibration->save(); // Should trigger listener

    expect($calibration->id)->not->toBeNull();
    $this->assertDatabaseHas('checklists', [
        'calibration_id' => $calibration->id,
        'checklist_template_id' => $template->id,
    ]);
});

it('updates kit items via listener when input provided', function () {
    // Setup Kit Parent and Child
    $parent = ReferenceStandard::factory()->create(['name' => 'Kit Parent']);
    $child = ReferenceStandard::factory()->create([
        'name' => 'Kit Child',
        'parent_id' => $parent->id,
        'actual_value' => 10.00,
    ]);

    $kitItemsInput = [
        [
            'child_id' => $child->id,
            'new_actual_value' => 10.05,
        ],
    ];

    $calibration = new Calibration;
    $calibration->fill([
        'calibrated_item_id' => $parent->id,
        'calibrated_item_type' => ReferenceStandard::class,
        'calibration_date' => now(),
        'performed_by_id' => $this->user->id,
        'type' => 'internal',
        'result' => CalibrationResult::Approved,
    ]);

    $calibration->kitItemsInput = $kitItemsInput;
    $calibration->save();

    $child->refresh();
    expect($child->actual_value)->toEqual(10.05);
});
