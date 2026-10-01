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

    public function test_instrument_api_returns_criticality_fields()
    {
        $type = InstrumentType::factory()->create();
        $instrument = Instrument::factory()->create([
            'instrument_type_id' => $type->id,
            'criticality' => 'safety_nr12',
        ]);

        $response = $this->getJson("/api/v1/instruments/{$instrument->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.criticality', 'safety_nr12');
        $response->assertJsonPath('data.criticality_label', 'Segurança de Máquinas (NR-12)');
        $response->assertJsonPath('data.is_critical', true);
    }

    public function test_instrument_api_can_store_instrument_with_criticality()
    {
        $type = InstrumentType::factory()->create();

        $payload = [
            'name' => 'Pressure Transmitter NR-13',
            'serial_number' => 'PT-NR13-001',
            'instrument_type_id' => $type->id,
            'status' => 'active',
            'criticality' => 'safety_nr13',
            'mpe' => '0.05',
            'acquisition_date' => '2026-01-15',
        ];

        $response = $this->postJson('/api/v1/instruments', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('data.criticality', 'safety_nr13');
        $response->assertJsonPath('data.is_critical', true);
    }
}
