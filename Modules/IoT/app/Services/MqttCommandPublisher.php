<?php

declare(strict_types=1);

namespace Modules\IoT\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Contracts\MqttClient;
use PhpMqtt\Client\Facades\MQTT;

class MqttCommandPublisher
{
    protected ?MqttClient $client = null;

    /**
     * Publica comando padronizado para o nó via conexão MQTT persistente por worker.
     *
     * @return array<string, mixed>
     */
    public function publishCommand(
        string $gatewayId,
        string $nodeId,
        string $status,
        float $confidence,
        string $msgId
    ): array {
        $mqttStatus = match ($status) {
            'falha_confirmada' => 'fault_confirmed',
            'saudavel', 'normal' => 'healthy',
            'analise_pendente', 'indeterminado' => 'pending_analysis',
            'machine_off' => 'off',
            default => 'pending_analysis',
        };

        $logDisplayStatus = match ($mqttStatus) {
            'fault_confirmed' => 'FALHA CONFIRMADA (Vermelho)',
            'healthy' => 'SAUDÁVEL (Verde)',
            'pending_analysis' => 'EM ANÁLISE / DEGRADADO (Amarelo)',
            'off' => 'MÁQUINA DESLIGADA (Verde Piscante)',
        };

        $payload = [
            'command' => 'set_alarm_state',
            'status' => $mqttStatus,
            'fault_type' => ($status === 'falha_confirmada' || $status === 'desbalanceamento') ? 'desbalanceamento' : 'normal',
            'confidence' => $confidence,
            'ttl_seconds' => 60,
            'ref_msg' => $msgId,
        ];

        $topic = "v1/gateways/{$gatewayId}/nodes/{$nodeId}/commands";
        $encoded = json_encode($payload);

        if ($encoded === false) {
            return [];
        }

        try {
            $this->publishWithReconnect($topic, $encoded);

            Log::channel('iot_commands')->info("Ref: {$msgId} | Resposta: {$logDisplayStatus}", [
                'payload_enviado' => $payload,
            ]);

            return $payload;
        } catch (Exception $exception) {
            $this->resetConnection();
            Log::channel('iot_commands')->error("Ref: {$msgId} | Erro MQTT: {$exception->getMessage()}");

            return [];
        }
    }

    public function disconnect(): void
    {
        if ($this->client !== null) {
            try {
                if ($this->client->isConnected()) {
                    $this->client->disconnect();
                }
            } catch (Exception) {
                // Socket already closed
            }
        }

        $this->client = null;
    }

    protected function publishWithReconnect(string $topic, string $message): void
    {
        $wasReused = $this->client !== null && $this->client->isConnected();

        try {
            $client = $this->resolveClient();
            $client->publish($topic, $message, (int) config('iot.mqtt_qos', 1));
        } catch (Exception $exception) {
            $this->resetConnection();

            if (! $wasReused) {
                throw $exception;
            }

            $freshClient = $this->resolveClient();
            $freshClient->publish($topic, $message, (int) config('iot.mqtt_qos', 1));
        }
    }

    protected function resolveClient(): MqttClient
    {
        if ($this->client !== null && $this->client->isConnected()) {
            return $this->client;
        }

        $this->client = MQTT::connection('default');

        return $this->client;
    }

    protected function resetConnection(): void
    {
        $this->disconnect();
    }
}
