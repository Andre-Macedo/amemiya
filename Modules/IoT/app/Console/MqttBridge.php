<?php

declare(strict_types=1);

namespace Modules\IoT\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\IoT\Jobs\ProcessTelemetryJob;
use PhpMqtt\Client\Facades\MQTT;

class MqttBridge extends Command
{
    protected $signature = 'iot:mqtt-bridge';

    protected $description = 'Fica ouvindo o Broker MQTT e encaminha telemetria para a fila do Redis';

    public function handle(): void
    {
        $this->info('Iniciando Ponte MQTT...');
        $qos = (int) config('iot.mqtt_qos', 1);

        while (true) {
            try {
                // Client ID aleatório para evitar conflitos se o container reiniciar rápido
                $clientId = 'amemiya_bridge_'.substr(md5(uniqid()), 0, 6);
                $mqtt = MQTT::connection('default', $clientId);

                $this->info("Conectado como {$clientId}. Aguardando mensagens...");

                $mqtt->subscribe('sensors/+/telemetry', function (string $topic, string $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $mqtt->subscribe('v1/gateways/+/nodes/+/telemetry', function (string $topic, string $message) {
                    $this->processMessage($topic, $message);
                }, $qos);

                $mqtt->loop(true);
            } catch (Exception $exception) {
                $this->error("Conexão perdida: {$exception->getMessage()}");
                Log::channel('iot_telemetry')->error('MQTT Bridge: Falha na conexão. Tentando reconectar em 5s...', [
                    'error' => $exception->getMessage(),
                ]);
                sleep(5);
            }
        }
    }

    private function processMessage(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            Log::channel('iot_telemetry')->warning('MQTT Bridge: Payload JSON inválido descartado.', [
                'topic' => $topic,
            ]);

            return;
        }

        // Extração de IDs do tópico se faltar no JSON
        $parts = explode('/', $topic);
        if (str_starts_with($topic, 'v1/gateways/')) {
            $data['device_id'] = $data['device_id'] ?? $parts[2] ?? '';
            $data['node_id'] = $data['node_id'] ?? $parts[4] ?? '';
        }

        $deviceId = $data['device_id'] ?? null;
        $nodeId = $data['node_id'] ?? null;
        if (! is_string($deviceId) || $deviceId === '' || ! is_string($nodeId) || $nodeId === '') {
            Log::channel('iot_telemetry')->warning('MQTT Bridge: Telemetria sem device_id/node_id válidos descartada.', [
                'topic' => $topic,
            ]);

            return;
        }

        ProcessTelemetryJob::dispatch($data);
        $this->info("-> [Msg enfileirada] Gateway: {$deviceId} | Node: {$nodeId}");
    }
}
