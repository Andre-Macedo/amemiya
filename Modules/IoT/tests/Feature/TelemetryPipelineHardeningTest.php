<?php

declare(strict_types=1);

namespace Modules\IoT\Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Modules\IoT\Jobs\ProcessTelemetryJob;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Models\IoTNode;
use Modules\IoT\Models\IoTSensorData;
use Modules\IoT\Services\MachineLearningService;
use Modules\IoT\Services\MqttCommandPublisher;
use Modules\System\Models\Machine;
use Modules\System\Models\Station;
use Modules\System\Models\User;
use PhpMqtt\Client\Contracts\MqttClient;
use PhpMqtt\Client\Facades\MQTT;
use Tests\TestCase;

class TelemetryPipelineHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected IoTGateway $gateway;

    protected IoTNode $node;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise',
            'price' => 0,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Hardening Test Tenant',
            'slug' => 'hardening-test-tenant',
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

        tenancy()->initialize($this->tenant);

        $station = Station::create([
            'name' => 'Bancada Hardening',
            'type' => 'physical',
        ]);

        $machine = Machine::create([
            'tenant_id' => $this->tenant->id,
            'station_id' => $station->id,
            'name' => 'Motor Bancada 1/4 CV',
            'code' => 'MTR-14CV',
            'status' => 'operational',
            'metadata' => [
                'iso_group' => 'small_machine',
                'foundation_type' => 'rigid',
                'nominal_rpm' => 1725,
            ],
        ]);

        $this->gateway = IoTGateway::create([
            'tenant_id' => $this->tenant->id,
            'station_id' => $station->id,
            'name' => 'Gateway Bancada',
            'device_id' => 'GW_HARD_01',
            'status' => 'offline',
        ]);

        $this->node = IoTNode::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'machine_id' => $machine->id,
            'name' => 'Probe Hardening 01',
            'node_id' => 'NODE_HARD_01',
            'status' => 'offline',
            'battery_voltage' => 3.9,
        ]);
    }

    public function test_ml_fallback_logs_warning_level_instead_of_info_when_cloud_ml_fails(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'upstream down'], 503),
        ]);
        MQTT::shouldReceive('connection')->andThrow(new Exception('Mocked MQTT offline'));

        $payload = [
            'device_id' => $this->gateway->device_id,
            'node_id' => $this->node->node_id,
            'ml_status' => 'saudavel',
            'confianca' => 0.45,
            'rms_global' => 0.12,
            'features' => [
                'v_rms' => 0.65,
            ],
        ];

        $job = new ProcessTelemetryJob($payload);
        $job->handle(app(MachineLearningService::class));

        $log = IoTDeviceLog::first();

        $this->assertNotNull($log);
        $this->assertSame('indeterminado', $log->cloud_ml_status);
        $this->assertSame('warning', $log->level);
    }

    public function test_mqtt_command_publisher_reuses_persistent_connection_across_publishes(): void
    {
        $mockClient = Mockery::mock(MqttClient::class);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('publish')->twice();

        MQTT::shouldReceive('connection')
            ->once()
            ->andReturn($mockClient);

        $publisher = new MqttCommandPublisher;
        $first = $publisher->publishCommand('GW_1', 'NODE_1', 'analise_pendente', 0.5, 'msg-1');
        $second = $publisher->publishCommand('GW_1', 'NODE_1', 'saudavel', 0.99, 'msg-2');

        $this->assertSame('pending_analysis', $first['status']);
        $this->assertSame('healthy', $second['status']);
    }

    public function test_telemetry_payload_validator_rejects_negative_rms_and_sanitizes_ds18b20_disconnect(): void
    {
        MQTT::shouldReceive('connection')->andThrow(new Exception('Mocked MQTT offline'));

        $invalidPayload = [
            'device_id' => $this->gateway->device_id,
            'node_id' => $this->node->node_id,
            'ml_status' => 'saudavel',
            'confianca' => 0.95,
            'rms_global' => -0.5,
            'velocity_rms' => 0.6,
        ];

        (new ProcessTelemetryJob($invalidPayload))->handle(app(MachineLearningService::class));
        $this->assertSame(0, IoTSensorData::count());

        $glitchTempPayload = [
            'device_id' => $this->gateway->device_id,
            'node_id' => $this->node->node_id,
            'ml_status' => 'saudavel',
            'confianca' => 0.95,
            'rms_global' => 0.25,
            'velocity_rms' => 0.6,
            'temp_celsius' => -127.0,
            'battery_voltage' => -1.0,
        ];

        (new ProcessTelemetryJob($glitchTempPayload))->handle(app(MachineLearningService::class));

        $sensorRow = IoTSensorData::first();
        $this->assertNotNull($sensorRow);
        $this->assertNull($sensorRow->temp_celsius);
        $this->assertSame(3.9, $this->node->fresh()->battery_voltage);
    }

    public function test_process_telemetry_updates_last_seen_at_and_watchdog_marks_stale_devices_offline(): void
    {
        MQTT::shouldReceive('connection')->andThrow(new Exception('Mocked MQTT offline'));

        $payload = [
            'device_id' => $this->gateway->device_id,
            'node_id' => $this->node->node_id,
            'ml_status' => 'saudavel',
            'confianca' => 0.96,
            'rms_global' => 0.22,
            'velocity_rms' => 0.65,
            'battery_voltage' => 4.05,
        ];

        (new ProcessTelemetryJob($payload))->handle(app(MachineLearningService::class));

        $this->gateway->refresh();
        $this->node->refresh();

        $this->assertSame('online', $this->gateway->status);
        $this->assertNotNull($this->gateway->last_seen_at);
        $this->assertSame('active', $this->node->status);
        $this->assertNotNull($this->node->last_seen_at);
        $this->assertSame(4.05, $this->node->battery_voltage);

        $neverSeenNode = IoTNode::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'machine_id' => $this->node->machine_id,
            'name' => 'Probe Never Seen',
            'node_id' => 'NODE_NEVER_SEEN',
            'status' => 'active',
        ]);

        $this->gateway->update(['last_seen_at' => now()->subMinutes(10)]);
        $this->node->update(['last_seen_at' => now()->subMinutes(10)]);

        $this->artisan('iot:mark-offline-devices')
            ->assertSuccessful();

        $this->assertSame('offline', $this->gateway->fresh()->status);
        $this->assertSame('offline', $this->node->fresh()->status);
        $this->assertSame('active', $neverSeenNode->fresh()->status);
    }

    public function test_process_telemetry_persists_enriched_iot_sensor_data_and_exposes_history_api(): void
    {
        MQTT::shouldReceive('connection')->andThrow(new Exception('Mocked MQTT offline'));

        $this->node->machine->update([
            'metadata' => [
                'iso_group' => 'small_machine',
                'foundation_type' => 'rigid',
                'nominal_rpm' => 1725,
                'bearing_model' => '6203',
            ],
        ]);

        $payload = [
            'device_id' => $this->gateway->device_id,
            'node_id' => $this->node->node_id,
            'msg_id' => 1042,
            'rpm_estimated' => 1725.0,
            'rpm_source' => 'accelerometer_firmware',
            'rms_global' => 5.85,
            'velocity_rms' => 7.40,
            'temp_celsius' => 58.2,
            'battery_voltage' => 3.88,
            'time_domain' => [
                'rms_x' => 4.39,
                'rms_y' => 4.80,
                'rms_z' => 5.26,
                'kurt_x' => 8.6,
                'kurt_y' => 9.1,
                'kurt_z' => 7.8,
            ],
            'peaks' => [
                ['freq_hz' => 28.75, 'amp_g' => 0.42],
                ['freq_hz' => 87.77, 'amp_g' => 1.85],
            ],
            'ml_status' => 'falha_confirmada',
            'ml_confidence' => 0.94,
            'timestamp' => now()->toIso8601String(),
        ];

        (new ProcessTelemetryJob($payload))->handle(app(MachineLearningService::class));

        $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        Sanctum::actingAs($user);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/iot-history?node_id={$this->node->node_id}&period=24h");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.node_id', $this->node->id)
            ->assertJsonPath('data.0.probe_temperature', 58.2)
            ->assertJsonPath('data.0.rpm_measured', 1725)
            ->assertJsonPath('data.0.velocity_rms', 7.4)
            ->assertJsonPath('data.0.rms_x', 4.39)
            ->assertJsonPath('data.0.iso_zone', 'D')
            ->assertJsonPath('data.0.battery_voltage', 3.88)
            ->assertJsonPath('data.0.root_cause', 'Desgaste / Falha em Rolamento (possível região: pista externa (BPFO))');
    }
}
