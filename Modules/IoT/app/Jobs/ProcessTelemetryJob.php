<?php

namespace Modules\IoT\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\IoT\DTOs\TelemetryDataDTO;
use Modules\IoT\Events\AnomalyDetected;
use Modules\IoT\Events\SensorDataReceived;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Services\MachineLearningService;
use PhpMqtt\Client\Facades\MQTT;
use Illuminate\Queue\Middleware\WithoutOverlapping;

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
                ->expireAfter(60)
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

        try {
            // Log do Payload Completo de Entrada
            Log::channel('iot_telemetry')->info("--- INÍCIO [Ref: {$msgId}] ---", [
                'payload_recebido' => $this->payload
            ]);

            // 1. Identificação básica
            $gateway = IoTGateway::where('device_id', $this->payload['device_id'] ?? '')->first();
            if (! $gateway) return;

            $nodeId = $this->payload['node_id'] ?? null;
            $node = $gateway->nodes()->where('node_id', $nodeId)->first() ?? $gateway->nodes()->first();
            if (! $node) return;

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
            $mlConfidence = (float) ($this->payload['confianca'] ?? $this->payload['ml_confidence'] ?? 1.0);

            // 3. Handshake e IA
            $cloudMlStatus = null;
            $cloudMlConfidence = null;
            $reanalyzed = false;

            if (($mlConfidence < 0.80 || $mlStatus === 'desbalanceamento') && ! empty($this->payload['features'])) {
                
                // Handshake 1: Análise Pendente (Amarelo)
                $this->sendMqttCommand($gateway->device_id, $node->node_id, 'analise_pendente', $mlConfidence, $msgId);

                Log::channel('iot_ml')->info("Ref: {$msgId} | Analisando na Nuvem", [
                    'node' => $node->node_id,
                    'node_status' => $mlStatus
                ]);
                
                $mlResult = $mlService->predictAnomalia($this->payload['features']);
                
                $cloudMlStatus = $mlResult['status']; 
                $cloudMlConfidence = (float) ($mlResult['confidence'] ?? 0);
                $reanalyzed = true;

                $logStatus = ($cloudMlStatus === 'falha_confirmada') ? 'FALHA CONFIRMADA' : 'SAUDÁVEL';
                Log::channel('iot_ml')->info("Ref: {$msgId} | Veredito Nuvem: {$logStatus} ({$cloudMlConfidence})");

                // Handshake 2: Veredito Final
                $this->sendMqttCommand($gateway->device_id, $node->node_id, $cloudMlStatus, $cloudMlConfidence, $msgId);
                
            } else {
                // Handshake Único: Espelhamento
                $finalStatus = match ($mlStatus) {
                    'desbalanceamento' => 'falha_confirmada',
                    'desligada'        => 'machine_off',
                    default            => 'saudavel',
                };
                
                $this->sendMqttCommand($gateway->device_id, $node->node_id, $finalStatus, $mlConfidence, $msgId);
            }

            // 4. DTO e Broadcast
            $dto = new TelemetryDataDTO(
                tenantId: $gateway->tenant_id,
                machineId: $node->machine_id,
                nodeId: $node->node_id,
                msgId: null,
                rpm: $this->payload['rpm'] ?? null,
                rmsGlobal: (float) ($this->payload['rms_global'] ?? 0),
                timeDomain: [
                    'rms_x' => (float) ($this->payload['rms_x'] ?? $this->payload['features']['x_rms'] ?? 0),
                    'rms_y' => (float) ($this->payload['rms_y'] ?? $this->payload['features']['y_rms'] ?? 0),
                    'rms_z' => (float) ($this->payload['rms_z'] ?? $this->payload['features']['z_rms'] ?? 0),
                    'kurt_x' => (float) ($this->payload['kurt_x'] ?? $this->payload['features']['x_kurtosis'] ?? 0),
                    'kurt_y' => (float) ($this->payload['kurt_y'] ?? $this->payload['features']['y_kurtosis'] ?? 0),
                    'kurt_z' => (float) ($this->payload['kurt_z'] ?? $this->payload['features']['z_kurtosis'] ?? 0),
                ],
                micRms: (float) ($this->payload['mic_rms'] ?? $this->payload['features']['mic_rms'] ?? 0),
                features: $this->payload['features'] ?? [],
                mlStatus: $mlStatus,
                mlConfidence: $mlConfidence,
                timestamp: isset($this->payload['timestamp']) && $this->payload['timestamp'] > 0
                    ? \Illuminate\Support\Carbon::createFromTimestamp($this->payload['timestamp'])->toIso8601String()
                    : now()->toIso8601String(),
                cloudMlStatus: $cloudMlStatus,
                cloudMlConfidence: $cloudMlConfidence
            );

            event(new SensorDataReceived($dto));
            
            // 5. Alerta Push
            $alertStatus = $cloudMlStatus ?? $mlStatus;
            if (!in_array($alertStatus, ['saudavel', 'normal', 'desligada'])) {
                broadcast(new AnomalyDetected($gateway->tenant_id, $gateway->id, [
                    'status' => $alertStatus,
                    'confidence' => $cloudMlConfidence ?? $mlConfidence,
                    'node_id' => $node->node_id,
                    'reanalyzed' => $reanalyzed
                ]));
            }

            $totalTime = round((microtime(true) - $startTime) * 1000);
            Log::channel('iot_telemetry')->info("--- FIM [Ref: {$msgId}] em {$totalTime}ms ---");

        } catch (\Exception $e) {
            Log::error("IoT Error [Ref: {$msgId}]: " . $e->getMessage());
        }
    }

    /**
     * Envia comando MQTT padronizado com log do payload completo.
     */
    protected function sendMqttCommand(string $gwId, string $nodeId, string $status, float $confidence, $msgId): void
    {
        try {
            $mqttStatus = match ($status) {
                'falha_confirmada' => 'fault_confirmed',
                'saudavel', 'normal' => 'healthy',
                'analise_pendente' => 'pending_analysis',
                'machine_off' => 'off',
                default => 'healthy',
            };

            $logDisplayStatus = match ($mqttStatus) {
                'fault_confirmed'  => 'FALHA CONFIRMADA (Vermelho)',
                'healthy'          => 'SAUDÁVEL (Verde)',
                'pending_analysis' => 'EM ANÁLISE (Amarelo)',
                'off'              => 'MÁQUINA DESLIGADA (Verde Piscante)',
                default            => 'SAUDÁVEL',
            };

            $payload = [
                'command'     => 'set_alarm_state',
                'status'      => $mqttStatus,
                'fault_type'  => ($status === 'falha_confirmada' || $status === 'desbalanceamento') ? 'desbalanceamento' : 'normal',
                'confidence'  => (float) $confidence,
                'ttl_seconds' => 60,
                'ref_msg'     => $msgId
            ];

            $pubClientId = 'amemiya_pub_' . substr(md5(uniqid()), 0, 6);
            $mqtt = MQTT::connection('default', $pubClientId);
            $mqtt->publish("v1/gateways/{$gwId}/nodes/{$nodeId}/commands", json_encode($payload));
            $mqtt->disconnect();

            // Log detalhado do comando enviado
            Log::channel('iot_commands')->info("Ref: {$msgId} | Resposta: {$logDisplayStatus}", [
                'payload_enviado' => $payload
            ]);

        } catch (\Exception $e) {
            Log::channel('iot_commands')->error("Ref: {$msgId} | Erro MQTT: " . $e->getMessage());
        }
    }
}
