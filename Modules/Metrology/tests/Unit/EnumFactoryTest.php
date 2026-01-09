<?php

namespace Modules\Metrology\Tests\Unit;

use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnumFactoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_instrument_with_factory()
    {
        try {
            // Debugging Factory
            $instrument = Instrument::factory()->create();
            
            $this->assertNotNull($instrument);
            $this->assertInstanceOf(Instrument::class, $instrument);
            $this->assertNotNull($instrument->status);
            // Check if status is Enum
            $this->assertInstanceOf(\Modules\Metrology\Enums\ItemStatus::class, $instrument->status);
        } catch (\Throwable $e) {
            file_put_contents('debug_factory_error.txt', "FACTORY ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            throw $e;
        }
    }
}
