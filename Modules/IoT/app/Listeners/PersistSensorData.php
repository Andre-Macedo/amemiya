<?php

namespace Modules\IoT\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\IoT\Events\SensorDataReceived;
use Modules\IoT\Models\IoTSensorData;

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
        $data = $event->data;

        IoTSensorData::create([
            'tenant_id'          => $data->tenantId,
            'node_id'            => $data->nodeId,
            'msg_id'             => $data->msgId,
            'rpm'                => $data->rpm,
            'rms_global'         => $data->rmsGlobal,
            'rms_x'              => $data->timeDomain['rms_x'] ?? null,
            'rms_y'              => $data->timeDomain['rms_y'] ?? null,
            'rms_z'              => $data->timeDomain['rms_z'] ?? null,
            'kurt_x'             => $data->timeDomain['kurt_x'] ?? null,
            'kurt_y'             => $data->timeDomain['kurt_y'] ?? null,
            'kurt_z'             => $data->timeDomain['kurt_z'] ?? null,
            'piezo_rms'          => $data->piezo['rms'] ?? null,
            'piezo_pico_max'     => $data->piezo['pico_max'] ?? null,
            'piezo_fator_crista' => $data->piezo['fator_crista'] ?? null,
            'fft_data'           => $data->fftData ?? null,
            'ml_status'          => $data->mlStatus,
            'ml_confidence'      => $data->mlConfidence,
            'measured_at'        => $data->timestamp,
        ]);
    }
}
