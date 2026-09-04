<?php

use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\ChecklistTemplate;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\System\Models\User;

it('includes nested item and formatted as_found/as_left readings', function () {
    // Arrange
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $type = InstrumentType::factory()->create();
    $instrument = Instrument::factory()->create([
        'instrument_type_id' => $type->id,
        'name' => 'Teste Paquímetro',
        'status' => 'active',
    ]);

    $calibration = Calibration::create([
        'calibrated_item_type' => Instrument::class,
        'calibrated_item_id' => $instrument->id,
        'calibration_date' => now(),
        'result' => 'approved',
        'type' => 'internal',
        'performed_by_id' => User::factory()->create()->id,
        'temperature' => 22.5,
        'humidity' => 55.0,
    ]);

    $template = ChecklistTemplate::factory()->create();

    $checklist = Checklist::create([
        'calibration_id' => $calibration->id,
        'checklist_template_id' => $template->id,
        'completed' => true,
    ]);

    $calibration->update(['checklist_id' => $checklist->id]);

    ChecklistItem::create([
        'checklist_id' => $checklist->id,
        'step' => 'Medição 10mm',
        'question_type' => 'numeric',
        'as_found_readings' => [10.05, 10.06],
        'as_left_readings' => [10.01, 10.02],
        'adjusted' => true,
        'nominal_value' => 10.0,
        'order' => 1,
    ]);

    // Act
    $response = $this->getJson("/api/v1/metrology/calibrations/{$calibration->id}");

    // Assert
    $response->assertStatus(200);

    // Verify nested object
    $response->assertJsonPath('data.calibrated_item.name', 'Teste Paquímetro');

    // Verify formatted readings (API resource uses as_found for readings_formatted)
    $response->assertJsonPath('data.checklist_items.0.readings_formatted', '10.05 | 10.06');
    $response->assertJsonPath('data.checklist_items.0.as_left_readings.0', 10.01);
    $response->assertJsonPath('data.checklist_items.0.adjusted', true);
});
