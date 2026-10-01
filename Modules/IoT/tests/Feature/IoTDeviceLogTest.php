<?php

declare(strict_types=1);

namespace Modules\IoT\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Models\IoTNode;
use Modules\System\Models\Machine;
use Modules\System\Models\Station;
use Modules\System\Models\User;
use Tests\TestCase;

class IoTDeviceLogTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected IoTGateway $gateway;

    protected IoTNode $node;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Metrology',
            'slug' => 'acme-metrology',
        ]);
        tenancy()->initialize($this->tenant);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Sanctum::actingAs($this->user);

        $station = Station::create([
            'name' => 'Bancada de Testes 01',
            'type' => 'physical',
        ]);

        $machine = Machine::create([
            'tenant_id' => $this->tenant->id,
            'station_id' => $station->id,
            'name' => 'Motor Trifásico 5HP',
            'code' => 'MTR-001',
            'model' => 'WEG W22',
            'status' => 'operational',
        ]);

        $this->gateway = IoTGateway::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gateway Principal',
            'device_id' => 'GW_CENTRAL_01',
            'status' => 'active',
        ]);

        $this->node = IoTNode::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'machine_id' => $machine->id,
            'name' => 'Sensor Mancal Acoplamento',
            'node_id' => 'node_vib_01',
            'status' => 'active',
        ]);
    }

    public function test_it_can_list_and_paginate_iot_logs(): void
    {
        IoTDeviceLog::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'node_id' => $this->node->id,
            'level' => 'info',
            'event_type' => 'telemetry_received',
            'message' => 'Telemetria de rotina recebida',
            'measured_at' => now(),
        ]);

        IoTDeviceLog::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'node_id' => $this->node->id,
            'level' => 'critical',
            'event_type' => 'anomaly_detected',
            'ml_status' => 'desbalanceamento',
            'ml_confidence' => 0.9450,
            'message' => 'Desbalanceamento severo detectado',
            'measured_at' => now(),
        ]);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/iot-logs');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_it_filters_logs_by_anomalies_only(): void
    {
        IoTDeviceLog::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'node_id' => $this->node->id,
            'level' => 'info',
            'event_type' => 'telemetry_received',
            'message' => 'Leitura normal',
        ]);

        IoTDeviceLog::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'node_id' => $this->node->id,
            'level' => 'critical',
            'event_type' => 'anomaly_detected',
            'ml_status' => 'desbalanceamento',
            'ml_confidence' => 0.96,
            'message' => 'Alerta de falha',
        ]);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson('/api/v1/iot-logs?only_anomalies=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_type', 'anomaly_detected')
            ->assertJsonPath('data.0.level', 'critical');
    }

    public function test_it_returns_full_payload_in_show(): void
    {
        $log = IoTDeviceLog::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'node_id' => $this->node->id,
            'level' => 'critical',
            'event_type' => 'anomaly_detected',
            'ml_status' => 'desbalanceamento',
            'ml_confidence' => 0.9820,
            'rpm' => 1750,
            'rms_global' => 4.3521,
            'raw_payload' => ['raw_x' => [1, 2, 3], 'raw_y' => [4, 5, 6]],
            'features' => ['x_rms' => 2.4, 'y_rms' => 3.1],
            'sent_command' => ['command' => 'set_alarm_state', 'status' => 'fault_confirmed'],
            'message' => 'Falha confirmada pela IA',
        ]);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/iot-logs/{$log->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.rpm', 1750)
            ->assertJsonPath('data.raw_payload.raw_x.0', 1)
            ->assertJsonPath('data.sent_command.status', 'fault_confirmed');
    }

    public function test_prune_data_command_cleans_old_iot_logs(): void
    {
        DB::table('iot_device_logs')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenant->id,
            'level' => 'info',
            'event_type' => 'telemetry_received',
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ]);

        DB::table('iot_device_logs')->insert([
            'id' => (string) Str::ulid(),
            'tenant_id' => $this->tenant->id,
            'level' => 'info',
            'event_type' => 'telemetry_received',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $this->artisan('iot:prune-data --days=30')
            ->expectsOutputToContain('1 logs removidos')
            ->assertExitCode(0);

        $this->assertDatabaseCount('iot_device_logs', 1);
    }
}
