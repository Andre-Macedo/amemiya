<?php

namespace Modules\IoT\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\IoT\DTOs\TelemetryDataDTO;
use Modules\IoT\Events\AnomalyDetected;
use Modules\IoT\Events\SensorDataReceived;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Services\MachineLearningService;

class ProcessTelemetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array  $payload  Dados brutos vindos do MQTT (Bridge)
     */
    public function __construct(protected array $payload) {}

    /**
     * Executa o processamento do dado (Worker Redis).
     */
    public function handle(MachineLearningService $mlService): void
    {
        try {
            // 1. Identificação básica (Gateway e Nó)
            $gateway = IoTGateway::where('device_id', $this->payload['device_id'])->first();
            if (! $gateway) {
                return;
            }

            $nodeId = $this->payload['node_id'] ?? null;
            $node = $nodeId
                ? $gateway->nodes()->where('node_id', $nodeId)->first()
                : $gateway->nodes()->first();

            if (! $node) {
                return;
            }

            // 2. Análise de Machine Learning (Python)
            // Só chamamos o ML multimodal se o payload contiver dados brutos (ondas)
            $hasRawWaves = isset($this->payload['radial']) && ! empty($this->payload['radial']);
            
            if ($hasRawWaves) {
                $mlResult = $mlService->analyzeTelemetry($this->payload);
            } else {
                // Se for apenas telemetria simples (como do simulador), usa o que veio ou default
                $mlResult = [
                    'status' => $this->payload['ml_status'] ?? 'normal',
                    'confidence' => (float) ($this->payload['ml_confidence'] ?? 1.0),
                ];
            }

            // 3. Criação do DTO estruturado
            $dto = new TelemetryDataDTO(
                tenantId: $gateway->tenant_id,
                machineId: $node->machine_id,
                nodeId: $node->node_id,
                msgId: $this->payload['msg_id'] ?? 0,
                rpm: $this->payload['rpm'] ?? 0,
                rmsGlobal: (float) ($this->payload['rms_global'] ?? 0),
                timeDomain: $this->payload['time_domain'] ?? [],
                piezo: $this->payload['piezo'] ?? [],
                mlStatus: $mlResult['status'],
                mlConfidence: $mlResult['confidence'],
                timestamp: isset($this->payload['timestamp'])
                    ? now()->timestamp($this->payload['timestamp'])->toIso8601String()
                    : now()->toIso8601String()
            );

            // 4. Broadcast em Tempo Real (Reverb)
            broadcast(new SensorDataReceived($dto));

            Log::info("IoT: Tentando salvar dado no DB para gateway: {$gateway->device_id}");

            // 5. Persistência em Banco de Dados (Bypassing Eloquent scopes for worker safety)
            try {
                \Illuminate\Support\Facades\DB::table('iot_sensor_data')->insert([
                    'id'                 => (string) str()->ulid(),
                    'tenant_id'          => $gateway->tenant_id,
                    'node_id'            => $node->id,
                    'msg_id'             => $dto->msgId,
                    'rpm'                => $dto->rpm,
                    'rms_global'         => $dto->rmsGlobal,
                    'rms_x'              => $dto->timeDomain['rms_x'] ?? null,
                    'rms_y'              => $dto->timeDomain['rms_y'] ?? null,
                    'rms_z'              => $dto->timeDomain['rms_z'] ?? null,
                    'kurt_x'             => $dto->timeDomain['kurt_x'] ?? null,
                    'kurt_y'             => $dto->timeDomain['kurt_y'] ?? null,
                    'kurt_z'             => $dto->timeDomain['kurt_z'] ?? null,
                    'piezo_rms'          => $dto->piezo['rms'] ?? null,
                    'piezo_pico_max'     => $dto->piezo['pico_max'] ?? null,
                    'piezo_fator_crista' => $dto->piezo['fator_crista'] ?? null,
                    'fft_data'           => json_encode($this->payload['fft'] ?? []),
                    'ml_status'          => $dto->mlStatus,
                    'ml_confidence'      => $dto->mlConfidence,
                    'measured_at'        => \Illuminate\Support\Carbon::parse($dto->timestamp)->format('Y-m-d H:i:s'),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
                Log::info("IoT: Dado salvo com sucesso no DB.");
            } catch (\Exception $e) {
                Log::error("IoT: Erro ao inserir no banco: " . $e->getMessage());
            }

            // Se houve análise pesada de anomalia, dispara o evento específico com espectrograma
            if ($hasRawWaves) {
                broadcast(new AnomalyDetected($gateway->tenant_id, $gateway->id, $mlResult));
            }

            } catch (\Exception $e) {
            Log::error('IoT: Falha ao processar telemetria: '.$e->getMessage());
            }
    }
}
