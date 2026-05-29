<?php

namespace Modules\IoT\Console;

use Illuminate\Console\Command;
use Modules\IoT\Jobs\ProcessTelemetryJob;
use PhpMqtt\Client\Facades\MQTT;

class MqttBridge extends Command
{
    /**
     * O nome e a assinatura do comando no console.
     *
     * @var string
     */
    protected $signature = 'iot:mqtt-bridge';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Fica ouvindo o Broker MQTT e encaminha telemetria para a fila do Redis';

    /**
     * Executa o comando.
     */
    public function handle(): void
    {
        $this->info('Conectando ao Broker MQTT...');

        try {
            // Usando um ID fixo e exclusivo para o Bridge
            $mqtt = MQTT::connection('default', 'amemiya_bridge_service');

            // Inscreve-se em vários formatos de tópico possíveis
            $mqtt->subscribe('sensors/+/telemetry', function (string $topic, string $message) {
                $this->processMessage($topic, $message);
            }, 1);

            $mqtt->subscribe('v1/gateways/+/nodes/+/telemetry', function (string $topic, string $message) {
                $this->processMessage($topic, $message);
            }, 1);

            $mqtt->loop(true);

        } catch (\Exception $e) {
            $this->error('Erro na conexão MQTT: '.$e->getMessage());
        }
    }

    private function processMessage(string $topic, string $message): void
    {
        $this->info("Mensagem recebida no tópico: {$topic}");
        $data = json_decode($message, true);

        if ($data) {
            // Se não tiver device_id ou node_id no payload, tenta extrair do tópico
            $parts = explode('/', $topic);

            // Formato: v1/gateways/{GW_ID}/nodes/{NODE_ID}/telemetry
            if (str_starts_with($topic, 'v1/gateways/')) {
                if (!isset($data['device_id']) && isset($parts[2])) {
                    $data['device_id'] = $parts[2]; // Gateway ID
                }
                if (!isset($data['node_id']) && isset($parts[4])) {
                    $data['node_id'] = $parts[4];   // Node ID
                }
            } 
            // Formato: sensors/{NODE_ID}/telemetry
            elseif (str_starts_with($topic, 'sensors/')) {
                if (!isset($data['device_id']) && isset($parts[1])) {
                    $data['device_id'] = $parts[1]; 
                }
                if (!isset($data['node_id']) && isset($parts[1])) {
                    $data['node_id'] = $parts[1];
                }
            }

            ProcessTelemetryJob::dispatch($data);
            $this->info("Dados enviados para a fila (Gateway: " . ($data['device_id'] ?? 'N/A') . ", Node: " . ($data['node_id'] ?? 'N/A') . ")");
        } else {
            $this->error('Payload inválido.');
        }
    }
}
