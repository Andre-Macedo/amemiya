<?php

namespace Modules\Metrology\Tests\Feature;

use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\InstrumentType;
use Tests\Concerns\HasSuperAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EventDrivenCalibrationTest extends TestCase
{
    use RefreshDatabase, HasSuperAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('module:migrate', ['module' => 'Metrology']);
        $this->user = $this->createSuperAdmin();
    }

    /** @test */
    public function it_creates_checklist_via_listener_when_input_provided()
    {
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
                    // Minimal required fields by logic
                ]
            ]
        ];

        // Simulate creation with transient data
        $calibration = new Calibration();
        $calibration->fill([
            'calibrated_item_id' => $instrument->id,
            'calibrated_item_type' => Instrument::class,
            'calibration_date' => now(),
            'performed_by_id' => $this->user->id,
            'type' => 'internal',
            // 'status' is not a column on Calibration, use 'result' if needed, or leave empty if testing creation
            'result' => \Modules\Metrology\Enums\CalibrationResult::Approved,
        ]);
        
        $calibration->checklistInput = $checklistData;
        $calibration->save(); // Should trigger listener

        $this->assertDatabaseHas('checklists', [
            'calibration_id' => $calibration->id,
            'checklist_template_id' => $template->id,
        ]);
    }

    /** @test */
    public function it_updates_kit_items_via_listener_when_input_provided()
    {
        // Setup Kit Parent and Child
        $parent = ReferenceStandard::factory()->create(['name' => 'Kit Parent']);
        $child = ReferenceStandard::factory()->create(['name' => 'Kit Child', 'parent_id' => $parent->id, 'actual_value' => 10.00]);

        $kitItemsInput = [
            [
                'child_id' => $child->id,
                'new_actual_value' => 10.05,
            ]
        ];

        $calibration = new Calibration();
        $calibration->fill([
            'calibrated_item_id' => $parent->id,
            'calibrated_item_type' => ReferenceStandard::class,
            'calibration_date' => now(),
            'performed_by_id' => $this->user->id,
            'type' => 'internal',
            'result' => \Modules\Metrology\Enums\CalibrationResult::Approved, 
        ]);

        $calibration->kitItemsInput = $kitItemsInput;
        $calibration->save();

        $child->refresh();
        $this->assertEquals(10.05, $child->actual_value);
    }
}
