<?php

namespace Modules\IoT\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnomalyDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param string $tenantId ID do Tenant
     * @param string $gatewayId ID do Gateway
     * @param array $mlResult Resultado completo vindo do microserviço Python
     */
    public function __construct(
        public string $tenantId,
        public string $gatewayId,
        public array $mlResult
    ) {}

    /**
     * Define o canal de transmissão.
     */
    public function broadcastOn(): array
    {
        $tenantSlug = \App\Models\Tenant::find($this->tenantId)?->slug ?? $this->tenantId;
        
        return [
            new Channel("tenant.{$tenantSlug}.iot"),
        ];
    }

    /**
     * Nome do evento para o frontend.
     */
    public function broadcastAs(): string
    {
        return 'anomaly.detected';
    }

    /**
     * Dados enviados via Websocket.
     */
    public function broadcastWith(): array
    {
        return [
            'gateway_id' => $this->gatewayId,
            'status' => $this->mlResult['status'] ?? 'desconhecido',
            'confidence' => $this->mlResult['confidence'] ?? 0,
            'spectrogram_b64' => $this->mlResult['spectrogram_b64'] ?? null,
            'detected_at' => now()->toIso8601String(),
        ];
    }
}
