<?php

declare(strict_types=1);

namespace Modules\Metrology\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\System\Models\User;
use Tests\TestCase;

class CalibrationMatrixParserApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Laboratório Metrológico Alfa',
            'slug' => 'lab-alfa',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_parses_matrix_readings_via_api(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeader('X-Tenant-ID', (string) $this->tenant->id)
            ->postJson('/api/v1/calibrations/parse-matrix', [
                'raw_data' => "10.01\t10.02\t10.01\n20.00\t20.01\t20.02",
                'mapping_mode' => 'sequential',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('delimiter', "\t")
            ->assertJsonCount(2, 'rows')
            ->assertJsonPath('rows.0.readings', [10.01, 10.02, 10.01]);
    }

    public function test_parses_matrix_with_nominal_matching_mode(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeader('X-Tenant-ID', (string) $this->tenant->id)
            ->postJson('/api/v1/calibrations/parse-matrix', [
                'raw_data' => "10.0\t10.01\t10.02\n50.0\t50.05\t50.04",
                'mapping_mode' => 'match_nominal',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(10.0, $response->json('rows.0.nominal'));
        $this->assertEquals(50.0, $response->json('rows.1.nominal'));
    }

    public function test_rejects_empty_raw_data(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->withHeader('X-Tenant-ID', (string) $this->tenant->id)
            ->postJson('/api/v1/calibrations/parse-matrix', [
                'raw_data' => '   ',
            ]);

        $response->assertStatus(422);
    }
}
