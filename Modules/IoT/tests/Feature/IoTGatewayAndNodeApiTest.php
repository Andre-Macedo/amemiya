<?php

declare(strict_types=1);

namespace Modules\IoT\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Models\IoTNode;
use Modules\System\Models\Machine;
use Modules\System\Models\Station;
use Modules\System\Models\User;
use Tests\TestCase;

class IoTGatewayAndNodeApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected Tenant $otherTenant;

    protected User $user;

    protected Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Metrology Lab',
            'slug' => 'acme-metrology-lab',
        ]);

        $this->otherTenant = Tenant::create([
            'name' => 'Competitor Lab',
            'slug' => 'competitor-lab',
        ]);

        tenancy()->initialize($this->tenant);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Sanctum::actingAs($this->user);

        $station = Station::create([
            'name' => 'Bancada Metrológica Primária',
            'type' => 'physical',
        ]);

        $this->machine = Machine::create([
            'tenant_id' => $this->tenant->id,
            'station_id' => $station->id,
            'name' => 'Bancada de Teste Vibração M1',
            'code' => 'BC-VIB-01',
            'status' => 'operational',
        ]);
    }

    public function test_it_can_create_list_and_update_gateways(): void
    {
        // 1. Create
        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/iot-gateways', [
                'name' => 'Gateway LoRa Concentrador 01',
                'device_id' => 'GW_LORA_001',
                'status' => 'online',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Gateway LoRa Concentrador 01')
            ->assertJsonPath('data.device_id', 'GW_LORA_001')
            ->assertJsonPath('data.status', 'online');

        $gatewayId = $response->json('data.id');

        // 2. Index
        $listResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/iot-gateways');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $gatewayId);

        // 3. Show
        $showResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/iot-gateways/{$gatewayId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $gatewayId);

        // 4. Update
        $updateResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/v1/iot-gateways/{$gatewayId}", [
                'name' => 'Gateway LoRa Concentrador 01 - Renomeado',
                'status' => 'offline',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Gateway LoRa Concentrador 01 - Renomeado')
            ->assertJsonPath('data.status', 'offline');

        // 5. Delete
        $deleteResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/v1/iot-gateways/{$gatewayId}");

        $deleteResponse->assertStatus(204);
        $this->assertSoftDeleted('iot_gateways', ['id' => $gatewayId]);
    }

    public function test_it_can_create_list_and_update_nodes_associated_with_gateway_and_machine(): void
    {
        $gateway = IoTGateway::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gateway Teste',
            'device_id' => 'GW_TEST_01',
            'status' => 'online',
        ]);

        // 1. Create Node
        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/iot-nodes', [
                'gateway_id' => $gateway->id,
                'machine_id' => $this->machine->id,
                'name' => 'Sensor Triaxial Mancal Dianteiro',
                'node_id' => 'NODE_MANCAL_01',
                'status' => 'online',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Sensor Triaxial Mancal Dianteiro')
            ->assertJsonPath('data.node_id', 'NODE_MANCAL_01')
            ->assertJsonPath('data.status', 'online');

        $nodeId = $response->json('data.id');

        // 2. Index
        $listResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/iot-nodes');

        $listResponse->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.node_id', 'NODE_MANCAL_01');

        // 3. Show
        $showResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/iot-nodes/{$nodeId}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('data.id', $nodeId)
            ->assertJsonPath('data.gateway.id', $gateway->id);

        // 4. Update
        $updateResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->putJson("/api/v1/iot-nodes/{$nodeId}", [
                'name' => 'Sensor Triaxial Mancal - Ajustado',
                'status' => 'maintenance',
            ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('data.name', 'Sensor Triaxial Mancal - Ajustado')
            ->assertJsonPath('data.status', 'maintenance');

        // 5. Delete
        $deleteResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->deleteJson("/api/v1/iot-nodes/{$nodeId}");

        $deleteResponse->assertStatus(204);
        $this->assertSoftDeleted('iot_nodes', ['id' => $nodeId]);
    }

    public function test_it_enforces_tenant_isolation_on_gateways_and_nodes(): void
    {
        // Cria gateway no outro tenant
        tenancy()->initialize($this->otherTenant);
        $foreignGateway = IoTGateway::create([
            'tenant_id' => $this->otherTenant->id,
            'name' => 'Gateway Concorrente Secreto',
            'device_id' => 'GW_SECRET_999',
            'status' => 'active',
        ]);

        // Volta ao tenant da sessão
        tenancy()->initialize($this->tenant);

        // Tenta listar - não deve aparecer
        $listResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/iot-gateways');

        $listResponse->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Tenta acessar diretamente via show do outro tenant - deve retornar 404 (escopo global do tenant)
        $showResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/iot-gateways/{$foreignGateway->id}");

        $showResponse->assertStatus(404);
    }
}
