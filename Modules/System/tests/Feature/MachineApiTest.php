<?php

declare(strict_types=1);

namespace Modules\System\Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\System\Models\Machine;
use Modules\System\Models\Station;
use Modules\System\Models\User;
use Tests\TestCase;

class MachineApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Tenant $otherTenant;

    protected User $user;

    protected Station $station;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise-plan',
            'price' => 199.00,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Acme Industrial Testing',
            'slug' => 'acme-industrial-testing',
            'plan_id' => $plan->id,
        ]);

        Subscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'name' => 'default',
            'gateway' => 'manual',
            'status' => 'active',
            'ends_at' => now()->addYear(),
        ]);

        $this->otherTenant = Tenant::create([
            'name' => 'Competitor Plant',
            'slug' => 'competitor-plant',
            'plan_id' => $plan->id,
        ]);

        Subscription::create([
            'tenant_id' => $this->otherTenant->id,
            'plan_id' => $plan->id,
            'name' => 'default',
            'gateway' => 'manual',
            'status' => 'active',
            'ends_at' => now()->addYear(),
        ]);

        tenancy()->initialize($this->tenant);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Sanctum::actingAs($this->user);

        $this->station = Station::create([
            'name' => 'Bancada Metrológica Dinâmica 01',
            'type' => 'physical',
        ]);
    }

    public function test_it_can_create_list_update_and_delete_machines(): void
    {
        // 1. Create
        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/machines', [
                'station_id' => $this->station->id,
                'name' => 'Motor de Indução WEG 15kW',
                'code' => 'MTR-WEG-015',
                'description' => 'Motor de bancada de testes de vibração',
                'status' => 'operational',
                'metadata' => [
                    'iso_group' => 'group_1',
                    'foundation_type' => 'rigid',
                    'rated_power_kw' => 15.0,
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Motor de Indução WEG 15kW')
            ->assertJsonPath('data.code', 'MTR-WEG-015')
            ->assertJsonPath('data.iso_group', 'group_1')
            ->assertJsonPath('data.foundation_type', 'rigid');

        $machineId = $response->json('data.id');

        // 2. Index
        $listResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/machines');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $machineId);

        // 3. Show
        $showResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/machines/{$machineId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $machineId)
            ->assertJsonPath('data.station.id', $this->station->id);

        // 4. Update
        $updateResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/v1/machines/{$machineId}", [
                'name' => 'Motor de Indução WEG 15kW - Revisado',
                'status' => 'maintenance',
                'metadata' => [
                    'iso_group' => 'group_1',
                    'foundation_type' => 'flexible',
                ],
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Motor de Indução WEG 15kW - Revisado')
            ->assertJsonPath('data.status', 'maintenance')
            ->assertJsonPath('data.foundation_type', 'flexible');

        // 5. Delete (SoftDelete)
        $deleteResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/v1/machines/{$machineId}");

        $deleteResponse->assertStatus(204);
        $this->assertSoftDeleted('machines', ['id' => $machineId]);
    }

    public function test_it_enforces_tenant_isolation_on_machines(): void
    {
        tenancy()->initialize($this->otherTenant);
        $foreignMachine = Machine::create([
            'tenant_id' => $this->otherTenant->id,
            'name' => 'Bancada Secreta Competidor',
            'code' => 'SECRET-01',
            'status' => 'operational',
        ]);

        tenancy()->initialize($this->tenant);

        // List should not include foreign machine
        $listResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/machines');

        $listResponse->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Show should return 404
        $showResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/machines/{$foreignMachine->id}");

        $showResponse->assertStatus(404);
    }
}
