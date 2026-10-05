<?php

declare(strict_types=1);

namespace Modules\IoT\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Models\IoTNode;
use Modules\IoT\Models\IoTSensorData;
use Modules\System\Models\Machine;
use Modules\System\Models\Station;

class IoTDatabaseSeeder extends Seeder
{
    /**
     * Run the IoT module seeds.
     */
    public function run(): void
    {
        $tenantId = tenant('id');

        $linhaProducao = Station::where('name', 'Linha de Montagem 01')->first();
        $labInternal = Station::where('name', 'Laboratório Central')->first();

        $tornoCnc = Machine::where('code', 'CNC-01')->first();
        $injetora = Machine::where('code', 'INJ-01')->first();
        $bancada = Machine::where('code', 'BCD-01')->first();
        $camara = Machine::where('code', 'CAM-01')->first();

        // 1. Gateways Industriais
        $gwGalpao = IoTGateway::updateOrCreate(
            ['device_id' => 'GW-GALPAO-A-01', 'tenant_id' => $tenantId],
            [
                'name' => 'Gateway Galpão A - LoRaWAN / MQTT',
                'station_id' => $linhaProducao?->id,
                'status' => 'online',
            ]
        );

        $gwLab = IoTGateway::updateOrCreate(
            ['device_id' => 'GW-LAB-METRO-01', 'tenant_id' => $tenantId],
            [
                'name' => 'Gateway Laboratório Metrologia',
                'station_id' => $labInternal?->id,
                'status' => 'online',
            ]
        );

        // 2. Nós de Borda (IoT Nodes)
        $nodeCnc = IoTNode::updateOrCreate(
            ['node_id' => 'NODE-CNC-01', 'tenant_id' => $tenantId],
            [
                'name' => 'Sensor Mancal Torno CNC Haas',
                'gateway_id' => $gwGalpao->id,
                'machine_id' => $tornoCnc?->id ?? Machine::first()?->id,
                'status' => 'active',
            ]
        );

        $nodeProbe = IoTNode::updateOrCreate(
            ['node_id' => 'NODE-PROBE-01', 'tenant_id' => $tenantId],
            [
                'name' => 'Probe Tower Bancada 01',
                'gateway_id' => $gwGalpao->id,
                'machine_id' => $bancada?->id ?? Machine::first()?->id,
                'status' => 'active',
            ]
        );

        $nodeInjetora = IoTNode::updateOrCreate(
            ['node_id' => 'NODE-INJ-01', 'tenant_id' => $tenantId],
            [
                'name' => 'Sensor Motor Injetora Romi',
                'gateway_id' => $gwGalpao->id,
                'machine_id' => $injetora?->id ?? Machine::first()?->id,
                'status' => 'active',
            ]
        );

        $nodeClima = IoTNode::updateOrCreate(
            ['node_id' => 'NODE-CLIMA-LAB-01', 'tenant_id' => $tenantId],
            [
                'name' => 'Monitor Climatização Lab Metrologia',
                'gateway_id' => $gwLab->id,
                'machine_id' => $camara?->id ?? Machine::first()?->id,
                'status' => 'active',
            ]
        );

        $nodes = [
            [
                'node' => $nodeCnc,
                'gw' => $gwGalpao,
                'type' => 'cnc',
            ],
            [
                'node' => $nodeProbe,
                'gw' => $gwGalpao,
                'type' => 'probe',
            ],
            [
                'node' => $nodeInjetora,
                'gw' => $gwGalpao,
                'type' => 'injetora',
            ],
            [
                'node' => $nodeClima,
                'gw' => $gwLab,
                'type' => 'clima',
            ],
        ];

        // 3. Telemetria Histórica (24 horas de registros horários por nó)
        $baseTime = now()->subHours(24);

        foreach ($nodes as $nodeConfig) {
            $node = $nodeConfig['node'];
            $gw = $nodeConfig['gw'];
            $type = $nodeConfig['type'];

            // Limpa dados prévios deste nó para reseed limpo
            IoTSensorData::where('node_id', $node->id)->delete();
            IoTDeviceLog::where('node_id', $node->id)->delete();

            for ($h = 0; $h <= 24; $h++) {
                $timestamp = (clone $baseTime)->addHours($h);

                $rpm = null;
                $rmsGlobal = 0.0;
                $rmsX = 0.0;
                $rmsY = 0.0;
                $rmsZ = 0.0;
                $kurtX = 3.0;
                $kurtY = 3.0;
                $kurtZ = 3.0;
                $micRms = 30.0;
                $mlStatus = 'normal';
                $mlConfidence = 0.98;
                $isoZone = 'A';
                $features = [];

                if ($type === 'cnc') {
                    // Torno CNC em operação de usinagem
                    $rpm = 2200 + rand(-100, 100);
                    $rmsGlobal = 1.40 + (rand(-20, 30) / 100);
                    $rmsX = 0.85 + (rand(-10, 15) / 100);
                    $rmsY = 1.10 + (rand(-15, 20) / 100);
                    $rmsZ = 1.30 + (rand(-10, 25) / 100);
                    $kurtX = 3.05;
                    $kurtY = 3.12;
                    $kurtZ = 2.98;
                    $micRms = 54.0 + (rand(-20, 20) / 10);
                    $isoZone = $rmsGlobal > 1.8 ? 'B' : 'A';
                    $mlStatus = 'normal';
                    $mlConfidence = 0.97;
                    $features = [
                        'temperature' => 43.5 + (rand(-10, 10) / 10),
                        'humidity' => 52.0 + (rand(-20, 20) / 10),
                        'peak_accel_g' => 3.2,
                        'crest_factor' => 2.3,
                        'dominant_freq_hz' => 36.6,
                    ];
                } elseif ($type === 'probe') {
                    // Probe Tower (Bancada de metrologia dimensional - estática)
                    $rpm = 0;
                    $rmsGlobal = 0.03 + (rand(0, 20) / 1000);
                    $rmsX = 0.015;
                    $rmsY = 0.018;
                    $rmsZ = 0.022;
                    $micRms = 28.5 + (rand(-10, 10) / 10);
                    $isoZone = 'A';
                    $mlStatus = 'normal';
                    $mlConfidence = 0.99;
                    $features = [
                        'temperature' => 20.1 + (rand(-2, 2) / 10),
                        'humidity' => 49.5 + (rand(-10, 10) / 10),
                        'probe_drift_um' => 0.04,
                    ];
                } elseif ($type === 'injetora') {
                    // Injetora: degradação induzida nas últimas 8 horas para demonstrar alerta e ML
                    $rpm = 1450 + rand(-50, 50);
                    $isDegraded = $h >= 17;

                    if ($isDegraded) {
                        // Zona C (Insatisfatória / Alerta)
                        $rmsGlobal = 4.60 + (($h - 16) * 0.15) + (rand(-10, 15) / 100);
                        $rmsX = 2.40 + (rand(-10, 20) / 100);
                        $rmsY = 2.90 + (rand(-10, 20) / 100);
                        $rmsZ = 4.20 + (rand(-10, 30) / 100);
                        $kurtZ = 4.65 + (rand(0, 30) / 100); // Indicativo de impacto no rolamento
                        $micRms = 72.5 + (rand(-15, 20) / 10);
                        $isoZone = 'C';
                        $mlStatus = 'desbalanceamento';
                        $mlConfidence = 0.88;
                        $features = [
                            'temperature' => 67.8 + (($h - 16) * 0.4),
                            'humidity' => 46.0,
                            'bearing_health_index' => max(40, 85 - (($h - 16) * 5)),
                            'crest_factor' => 4.8,
                        ];
                    } else {
                        // Operação normal
                        $rmsGlobal = 2.10 + (rand(-15, 20) / 100);
                        $rmsX = 1.10;
                        $rmsY = 1.30;
                        $rmsZ = 1.80;
                        $kurtZ = 3.10;
                        $micRms = 58.0 + (rand(-10, 10) / 10);
                        $isoZone = 'B';
                        $mlStatus = 'normal';
                        $mlConfidence = 0.95;
                        $features = [
                            'temperature' => 54.0 + (rand(-10, 10) / 10),
                            'humidity' => 48.0,
                            'bearing_health_index' => 92,
                            'crest_factor' => 2.6,
                        ];
                    }
                } elseif ($type === 'clima') {
                    // Monitor de Climatização do Laboratório
                    $rpm = 0;
                    $rmsGlobal = 0.01;
                    $rmsX = 0.005;
                    $rmsY = 0.005;
                    $rmsZ = 0.008;
                    $micRms = 22.0 + (rand(-5, 5) / 10);
                    $isoZone = 'A';
                    $mlStatus = 'normal';
                    $mlConfidence = 0.99;
                    $features = [
                        'temperature' => 20.0 + (rand(-3, 3) / 10),
                        'humidity' => 50.0 + (rand(-15, 15) / 10),
                        'air_pressure_hpa' => 1013.2,
                    ];
                }

                // Cria registro em iot_sensor_data
                IoTSensorData::create([
                    'tenant_id' => $tenantId,
                    'node_id' => $node->id,
                    'msg_id' => 1000 + $h,
                    'rpm' => $rpm,
                    'rms_global' => $rmsGlobal,
                    'rms_x' => $rmsX,
                    'rms_y' => $rmsY,
                    'rms_z' => $rmsZ,
                    'kurt_x' => $kurtX,
                    'kurt_y' => $kurtY,
                    'kurt_z' => $kurtZ,
                    'mic_rms' => $micRms,
                    'features' => $features,
                    'fft_data' => [0.08, 0.25, 0.95, 0.65, 0.32, 0.18, 0.09, 0.04],
                    'ml_status' => $mlStatus,
                    'ml_confidence' => $mlConfidence,
                    'cloud_ml_status' => $mlStatus,
                    'cloud_ml_confidence' => $mlConfidence,
                    'measured_at' => $timestamp,
                ]);

                // Cria registros de log operacionais em iot_device_logs para eventos chave
                if ($h % 4 === 0 || ($type === 'injetora' && $h >= 17)) {
                    $isWarning = $isoZone === 'C';
                    IoTDeviceLog::create([
                        'tenant_id' => $tenantId,
                        'gateway_id' => $gw->id,
                        'node_id' => $node->id,
                        'machine_id' => $node->machine_id,
                        'level' => $isWarning ? 'warning' : 'info',
                        'event_type' => $isWarning ? 'anomaly_detected' : 'telemetry_received',
                        'ml_status' => $mlStatus,
                        'ml_confidence' => $mlConfidence,
                        'cloud_ml_status' => $mlStatus,
                        'cloud_ml_confidence' => $mlConfidence,
                        'rpm' => $rpm,
                        'rms_global' => $rmsGlobal,
                        'velocity_rms' => $rmsGlobal,
                        'iso_zone' => $isoZone,
                        'iso_evaluation' => [
                            'zone' => $isoZone,
                            'threshold_zone_a' => 1.8,
                            'threshold_zone_b' => 4.5,
                            'threshold_zone_c' => 7.1,
                            'severity' => match ($isoZone) {
                                'A' => 'excelente',
                                'B' => 'aceitável',
                                'C' => 'insatisfatório',
                                default => 'inaceitável'
                            },
                        ],
                        'message' => $isWarning
                            ? "Alerta ISO 20816: Vibração Global ({$rmsGlobal} mm/s) atingiu Zona C. Risco de falha de rolamento detectado via ML."
                            : "Telemetria periódica recebida com sucesso. Condição operacional: Zona {$isoZone}.",
                        'features' => $features,
                        'raw_payload' => [
                            'node_id' => $node->node_id,
                            'rms' => $rmsGlobal,
                            'rpm' => $rpm,
                        ],
                        'measured_at' => $timestamp,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }
            }
        }
    }
}
