<?php

declare(strict_types=1);

namespace Modules\IoT\Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Models\IoTMLBurst;
use Modules\IoT\Models\IoTMLDataset;
use Modules\IoT\Models\IoTNode;
use Modules\System\Models\Machine;
use Modules\System\Models\Station;
use Modules\System\Models\User;
use Tests\TestCase;

class IoTMLDatasetTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected IoTGateway $gateway;

    protected IoTNode $node;

    protected Machine $machine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Acme Labs',
            'slug' => 'acme-labs',
        ]);
        tenancy()->initialize($this->tenant);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        Sanctum::actingAs($this->user);

        $station = Station::create([
            'name' => 'Bancada Dinâmica 01',
            'type' => 'physical',
        ]);

        $this->machine = Machine::create([
            'tenant_id' => $this->tenant->id,
            'station_id' => $station->id,
            'name' => 'Motor WEG 5HP',
            'code' => 'MTR-005',
            'model' => 'W22',
            'status' => 'operational',
        ]);

        $this->gateway = IoTGateway::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gateway Teste',
            'device_id' => 'GW_TEST_01',
            'status' => 'active',
        ]);

        $this->node = IoTNode::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'machine_id' => $this->machine->id,
            'name' => 'Sensor Mancal Acoplamento',
            'node_id' => 'node_01',
            'status' => 'active',
        ]);
    }

    public function test_it_can_create_a_ml_dataset(): void
    {
        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/iot-datasets', [
                'name' => 'Ensaio Desbalanceamento Bancada',
                'type' => 'supervised_xgboost',
                'target_machine_id' => $this->machine->id,
                'description' => 'Dataset para validação de desbalanceamento em regime de 1.750 RPM',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Ensaio Desbalanceamento Bancada')
            ->assertJsonPath('data.type', 'supervised_xgboost')
            ->assertJsonPath('data.status', 'collecting');

        $this->assertDatabaseHas('iot_ml_datasets', [
            'name' => 'Ensaio Desbalanceamento Bancada',
            'type' => 'supervised_xgboost',
        ]);
    }

    public function test_it_can_label_a_log_a_posteriori_into_dataset(): void
    {
        $log = IoTDeviceLog::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'node_id' => $this->node->id,
            'machine_id' => $this->machine->id,
            'level' => 'critical',
            'event_type' => 'anomaly_detected',
            'ml_status' => 'desbalanceamento',
            'ml_confidence' => 0.92,
            'cloud_ml_status' => 'falha_confirmada',
            'cloud_ml_confidence' => 0.9540,
            'rpm' => 1750,
            'rms_global' => 4.82,
            'features' => [
                'z_rms' => 3.2,
                'z_kurtosis' => 4.1,
                'y_rms' => 2.8,
            ],
            'message' => 'Disparo de Anomalia',
        ]);

        $dataset = IoTMLDataset::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Dataset Supervisado Motor 005',
            'slug' => 'dataset-motor-005',
            'type' => 'supervised_xgboost',
            'target_machine_id' => $this->machine->id,
            'status' => 'collecting',
        ]);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson("/api/v1/iot-logs/{$log->id}/label", [
                'ground_truth_label' => 'desbalanceamento',
                'dataset_id' => $dataset->id,
                'session_id' => 'corrida_bancada_10g',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.burst.ground_truth_label', 'desbalanceamento')
            ->assertJsonPath('data.burst.is_validated', true)
            ->assertJsonPath('data.dataset.class_distribution.desbalanceamento', 1)
            ->assertJsonPath('data.dataset.total_samples', 1);

        $this->assertDatabaseHas('iot_ml_bursts', [
            'device_log_id' => $log->id,
            'ground_truth_label' => 'desbalanceamento',
            'session_id' => 'corrida_bancada_10g',
            'is_validated' => true,
        ]);
    }

    public function test_it_can_export_dataset_as_csv_for_jupyter(): void
    {
        $dataset = IoTMLDataset::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Export Test Dataset',
            'slug' => 'export-test-dataset',
            'type' => 'supervised_xgboost',
        ]);

        IoTMLBurst::create([
            'tenant_id' => $this->tenant->id,
            'dataset_id' => $dataset->id,
            'session_id' => 'run_01',
            'rpm' => 1750,
            'rms_global' => 3.5,
            'ground_truth_label' => 'saudavel',
            'is_validated' => true,
            'features_summary' => [
                'z_rms' => 1.2,
                'z_kurtosis' => 3.0,
                'mic_rms' => 0.45,
            ],
        ]);

        IoTMLBurst::create([
            'tenant_id' => $this->tenant->id,
            'dataset_id' => $dataset->id,
            'session_id' => 'run_02',
            'rpm' => 1750,
            'rms_global' => 6.2,
            'ground_truth_label' => 'desbalanceamento',
            'is_validated' => true,
            'features_summary' => [
                'z_rms' => 4.8,
                'z_kurtosis' => 5.2,
                'mic_rms' => 1.25,
            ],
        ]);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->get("/api/v1/iot-datasets/{$dataset->id}/export");

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('burst_id,session_id,machine_id,rpm,rms_global,target_label,z_rms', $content);
        $this->assertStringContainsString('saudavel', $content);
        $this->assertStringContainsString('desbalanceamento', $content);
    }

    public function test_it_registers_and_deploys_models_in_registry(): void
    {
        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson('/api/v1/iot-models', [
                'name' => 'XGBoost Especialista MAFAULDA + Real',
                'model_type' => 'xgboost_cloud',
                'version' => 'v2.1.0',
                'target_device' => 'cloud',
                'status' => 'candidate',
                'metrics' => [
                    'accuracy' => 0.985,
                    'f1_score' => 0.978,
                ],
                'notes' => 'Modelo treinado com 50 rajadas rotuladas em bancada',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.version', 'v2.1.0')
            ->assertJsonPath('data.status', 'candidate');

        $modelId = $response->json('data.id');

        // Deploy to production
        $deployResponse = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->postJson("/api/v1/iot-models/{$modelId}/deploy", [
                'status' => 'in_production',
            ]);

        $deployResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'in_production');

        $this->assertDatabaseHas('iot_ml_models', [
            'id' => $modelId,
            'status' => 'in_production',
        ]);
    }
}
