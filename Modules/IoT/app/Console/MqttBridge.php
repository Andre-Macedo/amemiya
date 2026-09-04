<?php

namespace Modules\IoT\Console;

use Illuminate\Console\Command;
use Modules\IoT\Jobs\ProcessTelemetryJob;
use PhpMqtt\Client\Facades\MQTT;
use Illuminate\Support\Facades\Log;

class MqttBridge extends Command
{
    protected $signature = 'iot:mqtt-bridge';
    protected $description = 'Fica ouvindo o Broker MQTT e encaminha telemetria para a fila do Redis';

    public function handle(): void
    {
        $this->info('Iniciando Ponte MQTT...');
        
        while (true) {
            try {
                // Client ID aleatório para evitar conflitos se o container reiniciar rápido
                $clientId = 'amemiya_bridge_' . substr(md5(uniqid()), 0, 6);
                $mqtt = MQTT::connection('default', $clientId);

                $this->info("Conectado como {$clientId}. Aguardando mensagens...");

                $mqtt->subscribe('sensors/+/telemetry', function (string $topic, string $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $mqtt->subscribe('v1/gateways/+/nodes/+/telemetry', function (string $topic, string $message) {
                    $this->processMessage($topic, $message);
                }, 0);

                $mqtt->loop(true);

            } catch (\Exception $e) {
                $this->error('Conexão perdida: ' . $e->getMessage());
                Log::channel('iot_telemetry')->error('MQTT Bridge: Falha na conexão. Tentando reconectar em 5s...', [
                    'error' => $e->getMessage()
                ]);
                sleep(5);
            }
        }
    }

    private function processMessage(string $topic, string $message): void
    {
        $data = json_decode($message, true);

        if ($data) {
            // Extração de IDs do tópico se faltar no JSON
            $parts = explode('/', $topic);
            if (str_starts_with($topic, 'v1/gateways/')) {
                $data['device_id'] = $data['device_id'] ?? $parts[2] ?? 'N/A';
                $data['node_id'] = $data['node_id'] ?? $parts[4] ?? 'N/A';
            }

            ProcessTelemetryJob::dispatch($data);
            $this->info("-> [Msg enfileirada] Gateway: " . $data['device_id'] . " | Node: " . $data['node_id']);
        }
    }
}
