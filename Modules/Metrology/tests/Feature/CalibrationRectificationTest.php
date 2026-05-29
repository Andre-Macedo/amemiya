<?php

namespace Modules\Metrology\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Metrology\Actions\RectifyCalibrationAction;
use Modules\Metrology\Enums\CalibrationResult;
use Modules\Metrology\Models\Calibration;
use Modules\Metrology\Models\Checklist;
use Modules\Metrology\Models\ChecklistItem;
use Modules\Metrology\Models\Instrument;
use Modules\System\Models\User;
use Tests\TestCase;

class CalibrationRectificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_rectify_published_calibration()
    {
        // Arrange
        $user = User::factory()->create();
        $this->actingAs($user);

        $instrument = Instrument::factory()->create();

        // 1. Original Calibration (Published)
        $original = Calibration::factory()->create([
            'calibrated_item_type' => Instrument::class,
            'calibrated_item_id' => $instrument->id,
            'result' => CalibrationResult::Approved,
            'status' => 'published',
            'deviation' => 0.05,
        ]);

        // Add a checklist to verify cloning
        $checklist = Checklist::factory()->create(['calibration_id' => $original->id]);
        ChecklistItem::factory()->create([
            'checklist_id' => $checklist->id,
            'step' => 'Check 1',
            'readings' => [10.05],
        ]);

        $original->update(['checklist_id' => $checklist->id]);
        $original->refresh();

        // Act: Rectify
        $rectifyAction = new RectifyCalibrationAction;
        $newDraft = $rectifyAction->execute($original);

        // Assert
        // 1. New calibration is draft
        $this->assertEquals('draft', $newDraft->status);

        // 2. Links to parent
        $this->assertEquals($original->id, $newDraft->replaces_calibration_id);
        $this->assertTrue($newDraft->isRectification());

        // 3. Checklist cloned correctly

        $this->assertNotNull($newDraft->checklist, 'Checklist is null');
        $this->assertNotEquals($original->checklist_id, $newDraft->checklist_id, 'Checklist ID is same as original'); // Different IDs
        $this->assertCount(1, $newDraft->checklist->items);
        $this->assertEquals('Check 1', $newDraft->checklist->items->first()->step);

        // 4. Data carried over
        $this->assertEquals(0.05, $newDraft->deviation);
    }
}
