<?php

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\Enums\ItemStatus;
use Modules\Metrology\Exceptions\MetrologyException;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Services\CalibrationValidator;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CalibrationValidatorTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_active_items_to_be_calibrated()
    {
        $instrument = Instrument::factory()->create([
            'status' => ItemStatus::Active->value,
        ]);

        $validator = new CalibrationValidator();
        $this->assertTrue($validator->canBeCalibrated($instrument));
    }

    /** @test */
    public function it_prevents_rejected_items_from_being_calibrated()
    {
        $instrument = Instrument::factory()->create([
            'status' => ItemStatus::Rejected->value,
        ]);

        $this->expectException(MetrologyException::class);
        $this->expectExceptionMessage("Item status 'Reprovado' prevents calibration without maintenance.");

        $validator = new CalibrationValidator();
        $validator->canBeCalibrated($instrument);
    }

    /** @test */
    public function it_prevents_lost_items_from_being_calibrated()
    {
        // Assuming 'Lost' logic exists in Enum, checking backing value 'lost' or similar
        // Based on Phase 5 Enum: cases: Active, Inactive, UnderMaintenance, Rejected, Lost
        
        $instrument = Instrument::factory()->create([
            'status' => ItemStatus::Lost->value,
        ]);

        $this->expectException(MetrologyException::class);

        $validator = new CalibrationValidator();
        $validator->canBeCalibrated($instrument);
    }
}
