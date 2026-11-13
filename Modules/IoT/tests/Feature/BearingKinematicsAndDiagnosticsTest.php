<?php

declare(strict_types=1);

namespace Modules\IoT\Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\IoT\Events\AnomalyDetected;
use Modules\IoT\Events\SensorDataReceived;
use Modules\IoT\Jobs\ProcessTelemetryJob;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Models\IoTNode;
use Modules\IoT\Services\BearingKinematicsService;
use Modules\IoT\Services\ISO20816SeverityService;
use Modules\IoT\Services\MachineLearningService;
use Modules\IoT\Services\PrescriptiveDiagnosticsService;
use Modules\System\Models\Machine;
use Modules\System\Models\User;
use PhpMqtt\Client\Facades\MQTT;
use Tests\TestCase;

class BearingKinematicsAndDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected User $user;

    protected Machine $machine;

    protected IoTGateway $gateway;

    protected IoTNode $node;

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
            'name' => 'Laboratório Metrológico Preditivo',
            'slug' => 'lab-preditivo',
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

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $this->machine = Machine::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Bancada Didática M1',
            'code' => 'M1-LAB',
            'status' => 'operational',
            'metadata' => [
                'bearing_model' => 'skf_6204',
                'nominal_rpm' => 1800,
            ],
        ]);

        $this->gateway = IoTGateway::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gateway Principal',
            'device_id' => 'GW-BANCADA-01',
            'status' => 'online',
        ]);

        $this->node = IoTNode::create([
            'tenant_id' => $this->tenant->id,
            'gateway_id' => $this->gateway->id,
            'machine_id' => $this->machine->id,
            'name' => 'Sonda Mancal M1',
            'node_id' => 'NODE-001',
            'sensor_type' => 'vibration_adxl345',
            'status' => 'active',
        ]);
    }

    public function test_bearing_kinematics_calculates_correct_frequencies_for_skf_6204(): void
    {
        $service = new BearingKinematicsService;
        $result = $service->calculateForPreset('skf_6204', 1800.0);

        $this->assertSame('SKF 6204', $result['bearing_name']);
        $this->assertEquals(30.0, $result['shaft_frequency_hz']);

        // Em 1800 RPM (30 Hz): BPFO ~ 91.56 Hz, BPFI ~ 148.44 Hz
        $this->assertGreaterThan(90.0, $result['bpfo_hz']);
        $this->assertLessThan(93.0, $result['bpfo_hz']);

        $this->assertGreaterThan(147.0, $result['bpfi_hz']);
        $this->assertLessThan(150.0, $result['bpfi_hz']);

        $this->assertArrayHasKey('harmonics', $result);
        $this->assertCount(3, $result['harmonics']['bpfo']);
    }

    public function test_bearing_kinematics_matches_spectral_peaks(): void
    {
        $service = new BearingKinematicsService;

        $detectedPeaks = [
            ['frequency_hz' => 30.1, 'amplitude' => 1.85],  // Pico 1X
            ['frequency_hz' => 91.7, 'amplitude' => 0.62],  // Pico BPFO do SKF 6204
            ['frequency_hz' => 250.0, 'amplitude' => 0.05], // Ruído não coincidente
        ];

        $matches = $service->matchSpectralPeaks($detectedPeaks, 1800.0, 'skf_6204');

        $this->assertCount(2, $matches);
        $this->assertSame('1X_SHAFT', $matches[0]['fault_match']);
        $this->assertSame('BPFO', $matches[1]['fault_match']);
        $this->assertLessThan(1.0, $matches[1]['deviation_percent']);
    }

    public function test_prescriptive_diagnostics_generates_correct_root_cause_and_action(): void
    {
        $isoService = new ISO20816SeverityService;
        $diagnostics = new PrescriptiveDiagnosticsService($isoService);

        $isoEval = $isoService->evaluate(3.8, 'group_2', 'rigid'); // Zona C (Alerta: 2.8 a 4.5 mm/s)

        $matchedPeaks = [
            ['fault_match' => 'BPFO', 'amplitude' => 1.2, 'frequency_hz' => 91.6],
        ];

        $diagnosis = $diagnostics->generateDiagnosis(
            isoEvaluation: $isoEval,
            mlStatus: 'falha_rolamento',
            mlConfidence: 0.92,
            tempCelsius: 78.5,
            matchedPeaks: $matchedPeaks
        );

        $this->assertSame('C', $diagnosis['severity_zone']);
        $this->assertSame('warning', $diagnosis['thermal_status']);
        $this->assertStringContainsString('Pista Externa', $diagnosis['root_cause']);
        $this->assertNotEmpty($diagnosis['recommended_actions']);
        $this->assertStringContainsString('substituição preventiva', $diagnosis['recommended_actions'][2]);
    }

    public function test_iot_node_spectrum_endpoint_returns_kinematics_and_catalog(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/iot-nodes/{$this->node->id}/spectrum");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'node_id',
            'machine_id',
            'has_cached_spectrum',
            'bearing_kinematics' => [
                'bearing_name',
                'rpm',
                'shaft_frequency_hz',
                'bpfo_hz',
                'bpfi_hz',
                'bsf_hz',
                'ftf_hz',
                'orders',
                'harmonics',
            ],
            'available_presets',
        ]);

        $this->assertSame('NODE-001', $response->json('node_id'));
        $this->assertSame('SKF 6204', $response->json('bearing_kinematics.bearing_name'));
    }

    public function test_bearing_kinematics_estimates_rpm_from_peaks_even_when_2x_harmonic_is_stronger(): void
    {
        $service = new BearingKinematicsService;

        $peaks = [
            ['frequency_hz' => 19.0, 'amplitude' => 0.80],
            ['frequency_hz' => 38.0, 'amplitude' => 1.30],
            ['frequency_hz' => 57.0, 'amplitude' => 0.50],
            ['frequency_hz' => 240.0, 'amplitude' => 2.50],
        ];

        $estimated = $service->estimateRpmFromPeaks($peaks, 400.0, 3800.0);

        $this->assertNotNull($estimated);
        $this->assertEquals(1140.0, $estimated['rpm']);
        $this->assertEquals(19.0, $estimated['shaft_frequency_hz']);
        $this->assertEquals(3, $estimated['harmonic_hits']);
    }

    public function test_bearing_kinematics_extracts_interpolated_peaks_from_fft_array(): void
    {
        $service = new BearingKinematicsService;

        $fft = array_fill(0, 512, 0.01);
        $fft[5] = 0.25;
        $fft[6] = 0.90;
        $fft[7] = 0.65;
        $fft[12] = 0.10;
        $fft[13] = 0.45;
        $fft[14] = 0.20;

        $peaks = $service->extractPeaksFromFft($fft, 3200.0);

        $this->assertNotEmpty($peaks);
        $this->assertEqualsWithDelta(19.53, $peaks[0]['frequency_hz'], 0.35);

        $resolved = $service->resolveSpectralRpm(
            fftMagnitudes: $fft,
            sampleRate: 3200.0,
            nominalRpmFallback: 1750.0,
        );

        $this->assertSame('accelerometer_fft', $resolved['rpm_source']);
        $this->assertEqualsWithDelta(1172.0, $resolved['rpm'], 25.0);
    }

    public function test_process_telemetry_job_matches_bearing_fault_using_calculated_rpm_without_tachometer(): void
    {
        Event::fake();
        MQTT::shouldReceive('connection')->andThrow(new \RuntimeException('Mocked MQTT offline'));

        $payload = [
            'device_id' => $this->gateway->device_id,
            'node_id' => $this->node->node_id,
            'ml_status' => 'normal',
            'confianca' => 0.94,
            'rms_global' => 0.42,
            'velocity_rms' => 3.2,
            'peaks' => [
                ['frequency_hz' => 20.0, 'amplitude' => 0.95],
                ['frequency_hz' => 40.0, 'amplitude' => 0.40],
                ['frequency_hz' => 61.04, 'amplitude' => 1.15],
            ],
            'features' => [
                'v_rms' => 3.2,
            ],
        ];

        $job = new ProcessTelemetryJob($payload);
        $job->handle(app(MachineLearningService::class));

        $log = IoTDeviceLog::first();

        $matchedFaults = collect($log->iso_evaluation['matched_peaks'] ?? [])
            ->pluck('fault_match')
            ->all();

        $this->assertNotNull($log);
        $this->assertSame(1200, $log->rpm);
        $this->assertSame('accelerometer_fft', $log->iso_evaluation['rpm_source'] ?? null);
        $this->assertContains('BPFO', $matchedFaults);
        $this->assertStringContainsString('Pista Externa', $log->message);
    }

    public function test_prescriptive_diagnostics_does_not_default_unknown_zone_to_zone_a(): void
    {
        $isoService = new ISO20816SeverityService;
        $diagnostics = new PrescriptiveDiagnosticsService($isoService);

        $isoEval = $isoService->evaluate(null);

        $diagnosis = $diagnostics->generateDiagnosis(
            isoEvaluation: $isoEval,
            mlStatus: 'saudavel',
            mlConfidence: 0.95,
            tempCelsius: 40.0,
            matchedPeaks: []
        );

        $this->assertNull($diagnosis['severity_zone']);
        $this->assertSame('Não Determinado', $diagnosis['severity_label']);
        $this->assertStringContainsString('Insuficientes', $diagnosis['root_cause']);
        $this->assertSame(24, $diagnosis['urgency_hours']);
    }

    public function test_prescriptive_diagnostics_does_not_flag_normal_1x_peak_as_unbalance_in_zone_a(): void
    {
        $isoService = new ISO20816SeverityService;
        $diagnostics = new PrescriptiveDiagnosticsService($isoService);

        $isoEval = $isoService->evaluate(0.8, 'group_2', 'rigid');

        $diagnosis = $diagnostics->generateDiagnosis(
            isoEvaluation: $isoEval,
            mlStatus: 'saudavel',
            mlConfidence: 0.98,
            tempCelsius: 42.0,
            matchedPeaks: [
                ['fault_match' => '1X_SHAFT', 'amplitude' => 0.15, 'frequency_hz' => 29.2],
                ['fault_match' => '2X_SHAFT', 'amplitude' => 0.05, 'frequency_hz' => 58.4],
            ]
        );

        $this->assertSame('A', $diagnosis['severity_zone']);
        $this->assertSame('Operação Mecânica Estável', $diagnosis['root_cause']);
        $this->assertSame(720, $diagnosis['urgency_hours']);
    }

    public function test_process_telemetry_job_rejects_unknown_node_id_instead_of_assigning_to_first_node(): void
    {
        Event::fake([SensorDataReceived::class, AnomalyDetected::class]);
        MQTT::shouldReceive('connection')->andThrow(new \RuntimeException('Mocked MQTT offline'));

        $payload = [
            'device_id' => $this->gateway->device_id,
            'node_id' => 'NODE-INEXISTENTE',
            'ml_status' => 'desbalanceamento',
            'confianca' => 0.95,
            'rms_global' => 0.90,
            'velocity_rms' => 5.2,
        ];

        $job = new ProcessTelemetryJob($payload);
        $job->handle(app(MachineLearningService::class));

        $this->assertSame(0, IoTDeviceLog::count());
        Event::assertNotDispatched(SensorDataReceived::class);
        Event::assertNotDispatched(AnomalyDetected::class);
    }

    public function test_bearing_peak_in_healthy_zone_without_ml_anomaly_remains_indication_only(): void
    {
        $isoService = new ISO20816SeverityService;
        $diagnostics = new PrescriptiveDiagnosticsService($isoService);

        $isoEval = $isoService->evaluate(0.55, 'small_machine', 'rigid');

        $indicationOnly = $diagnostics->generateDiagnosis(
            isoEvaluation: $isoEval,
            mlStatus: 'saudavel',
            mlConfidence: 0.94,
            tempCelsius: 38.0,
            matchedPeaks: [
                ['fault_match' => 'BPFO', 'amplitude' => 0.12, 'frequency_hz' => 91.6],
            ]
        );

        $this->assertSame('kinematic_indication_only', $indicationOnly['confirmation_source']);
        $this->assertSame(['BPFO'], $indicationOnly['kinematic_indications']);
        $this->assertStringContainsString('Indicação Espectral', $indicationOnly['root_cause']);
        $this->assertSame(360, $indicationOnly['urgency_hours']);

        $confirmedByMl = $diagnostics->generateDiagnosis(
            isoEvaluation: $isoEval,
            mlStatus: 'falha_confirmada',
            mlConfidence: 0.91,
            tempCelsius: 38.0,
            matchedPeaks: [
                ['fault_match' => 'BPFO', 'amplitude' => 0.45, 'frequency_hz' => 91.6],
            ]
        );

        $this->assertSame('ml', $confirmedByMl['confirmation_source']);
        $this->assertSame('Defeito na Pista Externa do Rolamento (BPFO)', $confirmedByMl['root_cause']);
        $this->assertSame(48, $confirmedByMl['urgency_hours']);
    }

    public function test_machine_custom_bearing_geometry_calculates_sidebands_and_matches_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $this->machine->update([
            'metadata' => [
                'bearing_model' => 'custom',
                'nominal_rpm' => 1800,
                'bearing_geometry' => [
                    'bearing_name' => 'NSK 6203 ZZ Custom',
                    'n' => 8,
                    'd' => 6.75,
                    'D' => 28.5,
                    'alpha' => 0.0,
                ],
            ],
        ]);

        $response = $this->withHeader('X-Tenant-ID', $this->tenant->id)
            ->getJson("/api/v1/iot-nodes/{$this->node->id}/spectrum");

        $response->assertStatus(200)
            ->assertJsonPath('bearing_kinematics.bearing_name', 'NSK 6203 ZZ Custom')
            ->assertJsonStructure([
                'bearing_kinematics' => [
                    'harmonics' => ['bpfo', 'bpfi', '1x_shaft', 'bpfi_sidebands'],
                ],
                'matched_peaks',
            ]);

        $sidebands = $response->json('bearing_kinematics.harmonics.bpfi_sidebands');
        $bpfi = (float) $response->json('bearing_kinematics.bpfi_hz');
        $shaft = (float) $response->json('bearing_kinematics.shaft_frequency_hz');

        $this->assertCount(2, $sidebands);
        $this->assertEqualsWithDelta($bpfi - $shaft, $sidebands[0], 0.05);
        $this->assertEqualsWithDelta($bpfi + $shaft, $sidebands[1], 0.05);
    }
}
