<?php

declare(strict_types=1);

namespace Modules\IoT\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\IoT\Models\IoTGateway;
use Modules\IoT\Models\IoTNode;

class MarkOfflineDevices extends Command
{
    protected $signature = 'iot:mark-offline-devices
        {--gateway-minutes= : Minutos sem telemetria para marcar gateway como offline}
        {--node-minutes= : Minutos sem telemetria para marcar nó como offline}';

    protected $description = 'Watchdog de comunicação: marca gateways e nós sem telemetria recente como offline.';

    public function handle(): void
    {
        $defaultMinutes = (int) config('iot.offline_timeout_minutes', 5);
        $gatewayMinutes = (int) ($this->option('gateway-minutes') ?: $defaultMinutes);
        $nodeMinutes = (int) ($this->option('node-minutes') ?: $defaultMinutes);

        $gatewayCutoff = now()->subMinutes(max(1, $gatewayMinutes));
        $nodeCutoff = now()->subMinutes(max(1, $nodeMinutes));

        $offlineGateways = IoTGateway::query()
            ->where('status', 'online')
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<=', $gatewayCutoff)
            ->update(['status' => 'offline']);

        $offlineNodes = IoTNode::query()
            ->where('status', 'active')
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<=', $nodeCutoff)
            ->update(['status' => 'offline']);

        if ($offlineGateways > 0 || $offlineNodes > 0) {
            Log::channel('iot_telemetry')->warning('Watchdog IoT: dispositivos marcados como offline.', [
                'gateways' => $offlineGateways,
                'nodes' => $offlineNodes,
                'gateway_cutoff' => $gatewayCutoff->toIso8601String(),
                'node_cutoff' => $nodeCutoff->toIso8601String(),
            ]);
        }

        $this->comment("Watchdog concluído: {$offlineGateways} gateways e {$offlineNodes} nós marcados como offline.");
    }
}
