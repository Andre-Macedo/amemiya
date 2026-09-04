<?php

namespace Modules\IoT\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\IoT\Events\SensorDataReceived;
use Modules\IoT\Models\IoTSensorData;
use Illuminate\Support\Facades\Log;

class PersistSensorData
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SensorDataReceived $event): void
    {
        $dto = $event->data;

        try {
            // Buscamos o ID interno do Node pelo node_id (string) e tenant
            $node = \Modules\IoT\Models\IoTNode::where('tenant_id', $dto->tenantId)
                ->where('node_id', $dto->nodeId)
                ->first();

            if (!$node) {
                Log::error("IoT: Falha ao persistir - Node {$dto->nodeId} não encontrado.");
                return;
            }

            IoTSensorData::create([
                'tenant_id'           => $dto->tenantId,
                'node_id'             => $node->id, // Usar o ULID interno
                'msg_id'              => $dto->msgId,
                'rpm'                 => $dto->rpm,
                'rms_global'          => $dto->rmsGlobal,
                'rms_x'               => $dto->timeDomain['rms_x'] ?? null,
                'rms_y'               => $dto->timeDomain['rms_y'] ?? null,
                'rms_z'               => $dto->timeDomain['rms_z'] ?? null,
                'kurt_x'              => $dto->timeDomain['kurt_x'] ?? null,
                'kurt_y'              => $dto->timeDomain['kurt_y'] ?? null,
                'kurt_z'              => $dto->timeDomain['kurt_z'] ?? null,
                'mic_rms'             => $dto->micRms,
                'features'            => $dto->features,
                'ml_status'           => $dto->mlStatus,
                'ml_confidence'       => $dto->mlConfidence,
                'cloud_ml_status'     => $dto->cloudMlStatus,
                'cloud_ml_confidence' => $dto->cloudMlConfidence,
                'measured_at'         => \Illuminate\Support\Carbon::parse($dto->timestamp)->format('Y-m-d H:i:s'),
            ]);

            Log::channel('iot_telemetry')->info("Telemetria persistida com sucesso para o Node: " . $dto->nodeId);
            
        } catch (\Exception $e) {
            Log::channel('iot_telemetry')->error("Erro ao persistir telemetria: " . $e->getMessage());
        }
    }
}
