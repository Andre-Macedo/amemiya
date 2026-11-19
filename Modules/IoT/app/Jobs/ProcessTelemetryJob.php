<?php

namespace Modules\IoT\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Pulse\Facades\Pulse;
use Modules\IoT\DTOs\TelemetryDataDTO;
use Modules\IoT\Events\AnomalyDetected;
use Modules\IoT\Events\SensorDataReceived;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Services\BearingKinematicsService;
use Modules\IoT\Services\ISO20816SeverityService;
use Modules\IoT\Services\MachineLearningService;
use Modules\IoT\Services\MqttCommandPublisher;
use Modules\IoT\Services\PrescriptiveDiagnosticsService;
use Modules\IoT\Services\TelemetryPayloadValidator;

class ProcessTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Trava de concorrência: Garante que um Node seja processado por vez no Redis.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->payload['node_id'] ?? 'global'))
                ->releaseAfter(20)
                ->expireAfter(60),
        ];
    }

    /**
     * @param  array  $payload  Dados brutos vindos do MQTT (Bridge)
     */
    public function __construct(protected array $payload) {}

    /**
     * Executa o processamento do dado (Worker Redis).
     */
    public function handle(MachineLearningService $mlService): void
    {
        $startTime = microtime(true);
        $msgId = now()->format('H:i:s.v');
        $gateway = null;
        $node = null;
        $lastSentCommand = null;

        try {
            // Log do Payload Completo de Entrada (para fins de histórico do servidor)
            Log::channel('iot_telemetry')->info("--- INÍCIO [Ref: {$msgId}] ---", [
                'payload_recebido' => $this->payload,
            ]);

            // 1. Identificação básica
            $gateway = IoTGateway::where('device_id', $this->payload['device_id'] ?? '')->first();
            if (! $gateway) {
                return;
            }

            $nodeId = $this->payload['node_id'] ?? null;
            if (! is_string($nodeId) || $nodeId === '') {
                Log::channel('iot_telemetry')->warning("Ref: {$msgId} | Telemetria descartada: node_id ausente.", [
                    'device_id' => $gateway->device_id,
                ]);

                return;
            }

            $node = $gateway->nodes()->where('node_id', $nodeId)->first();
            if (! $node) {
                Log::channel('iot_telemetry')->warning("Ref: {$msgId} | Telemetria descartada: node_id '{$nodeId}' não pertence ao gateway '{$gateway->device_id}'.");

                return;
            }

            // 1.5 Validação estrita de integridade física do payload
            $validation = app(TelemetryPayloadValidator::class)->validate($this->payload);
            if (! $validation['valid']) {
                Log::channel('iot_telemetry')->warning("Ref: {$msgId} | Telemetria descartada por falha de validação.", [
                    'device_id' => $gateway->device_id,
                    'node_id' => $node->node_id,
                    'errors' => $validation['errors'],
                ]);

                return;
            }

            foreach ($validation['warnings'] as $warning) {
                Log::channel('iot_telemetry')->warning("Ref: {$msgId} | {$warning}", [
                    'node_id' => $node->node_id,
                ]);
            }

            // Atualiza heartbeat do gateway imediatamente ao receber payload válido
            $gateway->update([
                'last_seen_at' => now(),
                'status' => $gateway->status === 'offline' ? 'online' : $gateway->status,
            ]);

            // 2. Mapeamento de Status/Confiança
            $mlStatus = $this->payload['ml_status'] ?? null;
            if ($mlStatus === null && isset($this->payload['status_ia'])) {
                $mlStatus = match ((int) $this->payload['status_ia']) {
                    0 => 'saudavel',
                    1 => 'desbalanceamento',
                    2 => 'desligada',
                    default => 'indeterminado',
                };
            }
            $mlStatus = $mlStatus ?? 'normal';
            $mlConfidence = (float) ($this->payload['confianca'] ?? $this->payload['ml_confidence'] ?? 0.0);

            // 3. Handshake e IA
            $cloudMlStatus = null;
            $cloudMlConfidence = null;
            $reanalyzed = false;

            if (($mlConfidence < 0.80 || $mlStatus === 'desbalanceamento') && ! empty($this->payload['features'])) {

                // Handshake 1: Análise Pendente (Amarelo)
                $this->sendMqttCommand($gateway->device_id, $node->node_id, 'analise_pendente', $mlConfidence, $msgId);

                Log::channel('iot_ml')->info("Ref: {$msgId} | Analisando na Nuvem", [
                    'node' => $node->node_id,
                    'node_status' => $mlStatus,
                ]);

                $mlResult = $mlService->predictAnomalia($this->payload['features']);

                $cloudMlStatus = $mlResult['status'];
                $cloudMlConfidence = (float) ($mlResult['confidence'] ?? 0);
                $reanalyzed = true;

                $logStatus = match ($cloudMlStatus) {
                    'falha_confirmada' => 'FALHA CONFIRMADA',
                    'indeterminado', 'analise_pendente' => 'INDETERMINADO (ANÁLISE PENDENTE)',
                    default => 'SAUDÁVEL',
                };
                Log::channel('iot_ml')->info("Ref: {$msgId} | Veredito Nuvem: {$logStatus} ({$cloudMlConfidence})");

                // Handshake 2: Veredito Final
                $lastSentCommand = $this->sendMqttCommand($gateway->device_id, $node->node_id, $cloudMlStatus, $cloudMlConfidence, $msgId);

            } else {
                // Handshake Único: Espelhamento
                $finalStatus = match ($mlStatus) {
                    'desbalanceamento' => 'falha_confirmada',
                    'desligada' => 'machine_off',
                    default => 'saudavel',
                };

                $lastSentCommand = $this->sendMqttCommand($gateway->device_id, $node->node_id, $finalStatus, $mlConfidence, $msgId);
            }

            // 4. Resolução de RPM (Tacômetro ou Acelerômetro FFT) e Avaliação ISO 20816-3
            $kinematicsService = app(BearingKinematicsService::class);
            $machine = $node->machine;
            $machineMeta = $machine !== null ? ($machine->metadata ?? []) : [];
            $declaredRpmSource = $this->payload['rpm_source'] ?? $this->payload['features']['rpm_source'] ?? null;

            $rawRpm = isset($this->payload['rpm']) ? (float) $this->payload['rpm'] : null;
            $explicitRpm = ($rawRpm !== null && $declaredRpmSource !== 'accelerometer_firmware') ? $rawRpm : null;
            $payloadEstimatedRpm = isset($this->payload['rpm_estimated'])
                ? (float) $this->payload['rpm_estimated']
                : (isset($this->payload['features']['rpm_estimated'])
                    ? (float) $this->payload['features']['rpm_estimated']
                    : (isset($this->payload['features']['rpm']) ? (float) $this->payload['features']['rpm'] : null));
            $firmwareEstimatedRpm = $declaredRpmSource === 'accelerometer_firmware'
                ? ($rawRpm ?? $payloadEstimatedRpm)
                : $payloadEstimatedRpm;

            $sampleRate = (float) ($this->payload['sample_rate'] ?? $this->payload['spectrum']['sample_rate'] ?? 3200);
            $rawPeaks = is_array($this->payload['peaks'] ?? null) ? $this->payload['peaks'] : [];
            $fftForRpm = $this->payload['spectrum']['fft_x']
                ?? $this->payload['fft_x']
                ?? $this->payload['features']['fft_x']
                ?? $this->payload['spectrum']['fft_y']
                ?? $this->payload['fft_y']
                ?? $this->payload['features']['fft_y']
                ?? $this->payload['spectrum']['fft_z']
                ?? $this->payload['fft_z']
                ?? $this->payload['features']['fft_z']
                ?? [];

            $rpmResolution = $mlStatus === 'desligada'
                ? ['rpm' => 0.0, 'rpm_source' => 'machine_off', 'confidence' => null, 'extracted_peaks' => []]
                : $kinematicsService->resolveSpectralRpm(
                    explicitRpm: $explicitRpm,
                    firmwareEstimatedRpm: $firmwareEstimatedRpm,
                    peaks: $rawPeaks,
                    fftMagnitudes: is_array($fftForRpm) ? $fftForRpm : [],
                    sampleRate: $sampleRate,
                    nominalRpmFallback: isset($machineMeta['nominal_rpm']) ? (float) $machineMeta['nominal_rpm'] : null,
                    minRpm: (float) ($machineMeta['min_rpm'] ?? 400.0),
                    maxRpm: (float) ($machineMeta['max_rpm'] ?? 3800.0),
                );

            $rpm = $rpmResolution['rpm'] > 0.0 ? (int) round($rpmResolution['rpm']) : null;
            $detectedPeaks = ! empty($rawPeaks) ? $rawPeaks : $rpmResolution['extracted_peaks'];

            $isoService = app(ISO20816SeverityService::class);
            $rmsInG = isset($this->payload['rms_global']) ? (float) $this->payload['rms_global'] : null;
            $explicitVelo = isset($this->payload['features']['v_rms'])
                ? (float) $this->payload['features']['v_rms']
                : (isset($this->payload['velocity_rms']) ? (float) $this->payload['velocity_rms'] : null);

            $velocityRms = $isoService->calculateVelocityRms($rmsInG, $rpm, $explicitVelo);
            $machineGroup = (string) ($machineMeta['iso_group'] ?? 'group_2');
            $foundation = (string) ($machineMeta['foundation_type'] ?? 'rigid');
            $customThresholds = is_array($machineMeta['iso_thresholds'] ?? null) ? $machineMeta['iso_thresholds'] : null;
            $isoEval = $isoService->evaluate($velocityRms, $machineGroup, $foundation, $customThresholds);

            $tempCelsius = $validation['sanitized']['temp_celsius'];
            $batteryVoltage = $validation['sanitized']['battery_voltage'];

            // 4.1 Cache de Burst Espectral no Redis (TTL: 24h)
            $spectrumPayload = $this->payload['spectrum'] ?? null;
            if ($spectrumPayload === null && (isset($this->payload['fft_x']) || isset($this->payload['features']['fft_x']) || ! empty($detectedPeaks))) {
                $spectrumPayload = [
                    'fft_x' => $this->payload['fft_x'] ?? $this->payload['features']['fft_x'] ?? [],
                    'fft_y' => $this->payload['fft_y'] ?? $this->payload['features']['fft_y'] ?? [],
                    'fft_z' => $this->payload['fft_z'] ?? $this->payload['features']['fft_z'] ?? [],
                    'envelope_x' => $this->payload['envelope_x'] ?? $this->payload['features']['envelope_x'] ?? [],
                    'peaks' => $detectedPeaks,
                    'rpm' => $rpm,
                    'rpm_source' => $rpmResolution['rpm_source'],
                    'rpm_confidence' => $rpmResolution['confidence'],
                    'sample_rate' => $sampleRate,
                    'timestamp' => now()->toIso8601String(),
                ];
            } elseif (is_array($spectrumPayload)) {
                $spectrumPayload['rpm'] = $spectrumPayload['rpm'] ?? $rpm;
                $spectrumPayload['rpm_source'] = $spectrumPayload['rpm_source'] ?? $rpmResolution['rpm_source'];
                $spectrumPayload['rpm_confidence'] = $spectrumPayload['rpm_confidence'] ?? $rpmResolution['confidence'];
                $spectrumPayload['peaks'] = $spectrumPayload['peaks'] ?? $detectedPeaks;
            }

            if ($spectrumPayload !== null) {
                try {
                    Redis::setex("iot:node:{$node->node_id}:spectrum:latest", 86400, json_encode($spectrumPayload));
                } catch (\Throwable $e) {
                    Log::warning("Não foi possível salvar burst espectral no Redis: {$e->getMessage()}");
                }
            }

            // 4.2 Cinemática de Rolamento & Diagnóstico Prescritivo
            $matchedPeaks = [];
            if (! empty($detectedPeaks) && $rpmResolution['rpm'] > 0.0) {
                try {
                    $bearingData = $kinematicsService->calculateForMachine($machineMeta, $rpmResolution['rpm']);
                    $matchedPeaks = $kinematicsService->matchSpectralPeaksForKinematics(
                        $detectedPeaks,
                        $bearingData
                    );
                } catch (\Throwable) {
                    // Ignora modelo ausente
                }
            }

            $diagnosticsService = app(PrescriptiveDiagnosticsService::class);
            $prescriptive = $diagnosticsService->generateDiagnosis(
                $isoEval,
                $cloudMlStatus ?? $mlStatus,
                $cloudMlConfidence ?? $mlConfidence,
                $tempCelsius,
                $matchedPeaks
            );

            $effectiveMlStatus = $cloudMlStatus ?? $mlStatus;
            $effectiveConfidence = (float) ($cloudMlConfidence ?? $mlConfidence);
            $isMlAnomalyStatus = ! in_array($effectiveMlStatus, ['saudavel', 'normal', 'desligada', 'indeterminado'], true);
            $mlAnomalyProbability = $isMlAnomalyStatus
                ? round($effectiveConfidence, 4)
                : round(max(0.0, 1.0 - $effectiveConfidence), 4);

            $nodeUpdateData = [
                'last_seen_at' => now(),
                'status' => $node->status === 'offline' ? 'active' : $node->status,
                'last_payload' => [
                    'temperature' => $tempCelsius,
                    'probe_temperature' => $tempCelsius,
                    'battery_voltage' => $batteryVoltage ?? $node->battery_voltage,
                    'vibration_rms' => $velocityRms,
                    'rms_global' => $rmsInG,
                    'iso20816_severity' => $isoEval['zone'] ?? null,
                    'iso_applicable' => (bool) ($isoEval['iso_applicable'] ?? false),
                    'iso_group' => $machineGroup,
                    'ml_status' => $effectiveMlStatus,
                    'ml_confidence' => $effectiveConfidence,
                    'ml_anomaly_probability' => $mlAnomalyProbability,
                    'rpm_measured' => $rpm,
                    'rpm_source' => $rpmResolution['rpm_source'],
                    'root_cause' => $prescriptive['root_cause'],
                    'confirmation_source' => $prescriptive['confirmation_source'],
                    'created_at' => now()->toIso8601String(),
                ],
            ];
            if ($batteryVoltage !== null) {
                $nodeUpdateData['battery_voltage'] = $batteryVoltage;
            }
            $node->update($nodeUpdateData);

            $measuredAtCarbon = now();
            if (isset($this->payload['timestamp'])) {
                $rawTs = $this->payload['timestamp'];
                if (is_numeric($rawTs) && (float) $rawTs > 0) {
                    $measuredAtCarbon = Carbon::createFromTimestamp((int) $rawTs);
                } elseif (is_string($rawTs) && trim($rawTs) !== '') {
                    try {
                        $measuredAtCarbon = Carbon::parse($rawTs);
                    } catch (\Throwable) {
                        $measuredAtCarbon = now();
                    }
                }
            }

            $dto = new TelemetryDataDTO(
                tenantId: $gateway->tenant_id,
                machineId: $node->machine_id,
                nodeId: $node->node_id,
                msgId: isset($this->payload['msg_id']) && is_numeric($this->payload['msg_id']) ? (int) $this->payload['msg_id'] : null,
                rpm: $rpm,
                rmsGlobal: (float) ($this->payload['rms_global'] ?? 0),
                timeDomain: [
                    'rms_x' => (float) ($this->payload['rms_x'] ?? $this->payload['time_domain']['rms_x'] ?? $this->payload['features']['x_rms'] ?? 0),
                    'rms_y' => (float) ($this->payload['rms_y'] ?? $this->payload['time_domain']['rms_y'] ?? $this->payload['features']['y_rms'] ?? 0),
                    'rms_z' => (float) ($this->payload['rms_z'] ?? $this->payload['time_domain']['rms_z'] ?? $this->payload['features']['z_rms'] ?? 0),
                    'kurt_x' => (float) ($this->payload['kurt_x'] ?? $this->payload['time_domain']['kurt_x'] ?? $this->payload['features']['x_kurtosis'] ?? 0),
                    'kurt_y' => (float) ($this->payload['kurt_y'] ?? $this->payload['time_domain']['kurt_y'] ?? $this->payload['features']['y_kurtosis'] ?? 0),
                    'kurt_z' => (float) ($this->payload['kurt_z'] ?? $this->payload['time_domain']['kurt_z'] ?? $this->payload['features']['z_kurtosis'] ?? 0),
                ],
                micRms: (float) ($this->payload['mic_rms'] ?? $this->payload['features']['mic_rms'] ?? 0),
                features: is_array($this->payload['features'] ?? null) ? $this->payload['features'] : [],
                mlStatus: $mlStatus,
                mlConfidence: $mlConfidence,
                timestamp: $measuredAtCarbon->toIso8601String(),
                cloudMlStatus: $cloudMlStatus,
                cloudMlConfidence: $cloudMlConfidence,
                velocityRms: $velocityRms,
                isoZone: $isoEval['zone'] ?? null,
                tempCelsius: $tempCelsius,
                batteryVoltage: $batteryVoltage,
                prescriptiveDiagnosis: $prescriptive,
                isoApplicable: (bool) ($isoEval['iso_applicable'] ?? false),
                isoGroup: $machineGroup,
                isoEvaluation: $isoEval
            );

            event(new SensorDataReceived($dto));

            // 5. Alerta Push (IA ou Severidade ISO Crítica)
            $alertStatus = $cloudMlStatus ?? $mlStatus;
            $isIsoCritical = in_array($isoEval['zone'] ?? '', ['C', 'D'], true);
            $isAnomaly = (! in_array($alertStatus, ['saudavel', 'normal', 'desligada'], true)) || $isIsoCritical;

            if ($isAnomaly) {
                broadcast(new AnomalyDetected($gateway->tenant_id, $gateway->id, [
                    'status' => $alertStatus,
                    'confidence' => $cloudMlConfidence ?? $mlConfidence,
                    'node_id' => $node->node_id,
                    'reanalyzed' => $reanalyzed,
                    'iso_zone' => $isoEval['zone'] ?? null,
                    'velocity_rms' => $velocityRms,
                    'root_cause' => $prescriptive['root_cause'],
                    'prescriptive_action' => $prescriptive['recommended_actions'][0] ?? null,
                ]));
            }

            // 6. Registro de Auditoria / Diagnóstico Estruturado
            if ($isAnomaly || $reanalyzed || $isIsoCritical) {
                $level = match ($alertStatus) {
                    'falha_confirmada', 'desbalanceamento' => 'critical',
                    'analise_pendente', 'indeterminado' => 'warning',
                    default => ($isoEval['zone'] === 'D' ? 'critical' : ($isoEval['zone'] === 'C' ? 'warning' : 'info')),
                };

                $eventType = $isAnomaly ? 'anomaly_detected' : 'cloud_ml_evaluated';
                $isoText = $velocityRms !== null && isset($isoEval['zone'])
                    ? " | ISO 20816: Zona {$isoEval['zone']} ({$velocityRms} mm/s)"
                    : '';

                IoTDeviceLog::create([
                    'tenant_id' => $gateway->tenant_id,
                    'gateway_id' => $gateway->id,
                    'node_id' => $node->id,
                    'machine_id' => $node->machine_id,
                    'level' => $level,
                    'event_type' => $eventType,
                    'ml_status' => $mlStatus,
                    'ml_confidence' => $mlConfidence,
                    'cloud_ml_status' => $cloudMlStatus,
                    'cloud_ml_confidence' => $cloudMlConfidence,
                    'rpm' => $rpm,
                    'rms_global' => (float) ($this->payload['rms_global'] ?? 0),
                    'velocity_rms' => $velocityRms,
                    'iso_zone' => $isoEval['zone'] ?? null,
                    'iso_evaluation' => array_merge($isoEval, [
                        'rpm_source' => $rpmResolution['rpm_source'],
                        'rpm_confidence' => $rpmResolution['confidence'],
                        'matched_peaks' => $matchedPeaks,
                        'prescriptive_diagnosis' => $prescriptive,
                    ]),
                    'raw_payload' => $this->payload,
                    'features' => $this->payload['features'] ?? [],
                    'sent_command' => $lastSentCommand,
                    'message' => "Disparo de IA: Status Edge [{$mlStatus}] (".round($mlConfidence * 100, 1).'%)'.($reanalyzed ? " -> Nuvem [{$cloudMlStatus}] (".round((float) $cloudMlConfidence * 100, 1).'%)' : '').$isoText." | Causa-Raiz: {$prescriptive['root_cause']}",
                    'measured_at' => $measuredAtCarbon,
                ]);
            }

            $totalTime = round((microtime(true) - $startTime) * 1000);
            Log::channel('iot_telemetry')->info("--- FIM [Ref: {$msgId}] em {$totalTime}ms ---");

            // 7. Laravel Pulse Telemetria (se habilitado)
            if (class_exists(Pulse::class)) {
                Pulse::record(
                    type: 'iot_telemetry',
                    key: $node->node_id,
                    value: 1,
                    timestamp: now(),
                )->count();

                if ($reanalyzed) {
                    Pulse::record(
                        type: 'iot_ml_latency',
                        key: 'cloud_xgboost',
                        value: (int) $totalTime,
                        timestamp: now(),
                    )->avg();
                }
            }

        } catch (\Exception $e) {
            Log::error("IoT Error [Ref: {$msgId}]: ".$e->getMessage());

            if ($gateway && $node) {
                try {
                    IoTDeviceLog::create([
                        'tenant_id' => $gateway->tenant_id,
                        'gateway_id' => $gateway->id,
                        'node_id' => $node->id,
                        'machine_id' => $node->machine_id,
                        'level' => 'error',
                        'event_type' => 'error',
                        'raw_payload' => $this->payload,
                        'message' => 'Erro ao processar telemetria: '.$e->getMessage(),
                        'measured_at' => now(),
                    ]);
                } catch (\Exception $logEx) {
                    Log::error('IoT Device Log Error: '.$logEx->getMessage());
                }
            }

            if (app()->bound('sentry')) {
                \Sentry\captureException($e);
            }
        }
    }

    /**
     * Envia comando MQTT padronizado reutilizando cliente persistente no worker.
     *
     * @return array<string, mixed> O payload do comando enviado
     */
    protected function sendMqttCommand(string $gwId, string $nodeId, string $status, float $confidence, mixed $msgId): array
    {
        return app(MqttCommandPublisher::class)->publishCommand(
            gatewayId: $gwId,
            nodeId: $nodeId,
            status: $status,
            confidence: $confidence,
            msgId: (string) $msgId
        );
    }
}
