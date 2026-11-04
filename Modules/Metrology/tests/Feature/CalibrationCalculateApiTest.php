<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Metrology\Models\Instrument;
use Modules\Metrology\Models\InstrumentType;
use Modules\Metrology\Models\ReferenceStandard;
use Modules\Metrology\Models\ReferenceStandardType;
use Modules\System\Models\User;
use Tests\TestCase;

class CalibrationCalculateApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create([
            'name' => 'Metrology Test Lab',
            'slug' => 'metro-lab-calc',
        ]);
        tenancy()->initialize($tenant);

        $this->user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($this->user);
    }

    public function test_can_calculate_uncertainty_and_budget_via_api(): void
    {
        $instType = InstrumentType::factory()->create();
        $instrument = Instrument::factory()->create([
            'instrument_type_id' => $instType->id,
            'resolution' => '0.01',
        ]);

        $stdType = ReferenceStandardType::factory()->create();
        $standard = ReferenceStandard::factory()->create([
            'reference_standard_type_id' => $stdType->id,
            'nominal_value' => '10.0',
            'actual_value' => '10.0002',
            'uncertainty' => '0.0015',
        ]);

        $payload = [
            'instrument_id' => $instrument->id,
            'temperature' => 20.0,
            'items' => [
                [
                    'step' => 1,
                    'nominal_value' => 10.0,
                    'as_found_readings' => [10.01, 10.02, 10.01, 10.02, 10.01],
                    'standard_id' => $standard->id,
                ],
                [
                    'step' => 2,
                    'nominal_value' => 20.0,
                    'as_found_readings' => [20.02, 20.03, 20.02, 20.02, 20.03],
                    'standard_id' => $standard->id,
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/calibrations/calculate', $payload);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'uncertainty',
            'k_factor',
            'uncertainty_budget',
            'points',
        ]);

        $data = $response->json();
        $this->assertGreaterThan(0, $data['uncertainty']);
        $this->assertNotEmpty($data['uncertainty_budget']);
        $this->assertCount(2, $data['points']);
    }

    public function test_calculate_returns_zeros_when_no_readings_provided(): void
    {
        $payload = [
            'items' => [],
        ];

        $response = $this->postJson('/api/v1/calibrations/calculate', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'uncertainty' => 0.0,
            'k_factor' => 2.0,
            'uncertainty_budget' => [],
            'points' => [],
        ]);
    }

    public function test_can_calculate_uncertainty_with_multiple_reference_standards(): void
    {
        $instType = InstrumentType::factory()->create();
        $instrument = Instrument::factory()->create([
            'instrument_type_id' => $instType->id,
            'resolution' => '0.001',
        ]);

        $stdType = ReferenceStandardType::factory()->create();
        $std1 = ReferenceStandard::factory()->create([
            'reference_standard_type_id' => $stdType->id,
            'name' => 'Bloco 10mm',
            'actual_value' => '10.0001',
            'uncertainty' => '0.0015',
        ]);
        $std2 = ReferenceStandard::factory()->create([
            'reference_standard_type_id' => $stdType->id,
            'name' => 'Bloco 25mm',
            'actual_value' => '24.9998',
            'uncertainty' => '0.0020',
        ]);

        $payload = [
            'instrument_id' => $instrument->id,
            'items' => [
                [
                    'step' => 1,
                    'nominal_value' => 35.0,
                    'as_found_readings' => [35.001, 35.002, 35.001],
                    'standard_ids' => [$std1->id, $std2->id],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/calibrations/calculate', $payload);

        $response->assertStatus(200);
        $point = $response->json('points.0');
        $this->assertNotNull($point);
        $sources = array_column($point['budget'], 'source');
        $this->assertContains('Bloco 10mm', $sources);
        $this->assertContains('Bloco 25mm', $sources);
    }
}
