<?php

namespace Modules\IoT\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\IoT\DTOs\TelemetryDataDTO;

class SensorDataReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  TelemetryDataDTO  $data  O DTO estruturado com dados e predição
     */
    public function __construct(
        public TelemetryDataDTO $data
    ) {}

    /**
     * Define o canal no qual o evento será transmitido.
     */
    public function broadcastOn(): array
    {
        // Tenta encontrar o slug se o tenantId for um ULID, senão usa o que veio
        $tenantSlug = \App\Models\Tenant::find($this->data->tenantId)?->slug ?? $this->data->tenantId;
        
        return [
            new Channel("tenant.{$tenantSlug}.iot"),
        ];
    }

    /**
     * O nome do evento que o Next.js vai escutar (.listen('.sensor.data'))
     */
    public function broadcastAs(): string
    {
        return 'sensor.data';
    }

    /**
     * Dados que serão enviados no JSON do Websocket.
     */
    public function broadcastWith(): array
    {
        return $this->data->toArray();
    }
}
