<?php

namespace Modules\Metrology\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\System\Models\User;
use Tests\TestCase;

class InstrumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    public function test_pagination_respects_per_page_parameter()
    {
        // Arrange: Create 30 instruments
        $type = InstrumentType::factory()->create();
        Instrument::factory()->count(30)->create([
            'instrument_type_id' => $type->id,
            'status' => 'active',
        ]);

        // Act: Request with per_page = 5
        $response = $this->getJson('/api/v1/instruments?per_page=5');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(5, 'data');
        $response->assertJson([
            'meta' => [
                'per_page' => 5,
                'total' => 30,
                'last_page' => 6,
            ],
        ]);
    }

    public function test_pagination_defaults_to_20_when_parameter_missing()
    {
        // Arrange: Create 25 instruments
        $type = InstrumentType::factory()->create();
        Instrument::factory()->count(25)->create([
            'instrument_type_id' => $type->id,
            'status' => 'active',
        ]);

        // Act: Request without per_page
        $response = $this->getJson('/api/v1/instruments');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data'); // Should return default 20
        $response->assertJson([
            'meta' => [
                'per_page' => 20,
                'total' => 25,
            ],
        ]);
    }
}
