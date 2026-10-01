<?php

namespace Modules\IoT\Console\Commands;

use Illuminate\Console\Command;
use Modules\IoT\Models\IoTDeviceLog;
use Modules\IoT\Models\IoTSensorData;

class PruneSensorData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iot:prune-data {--days=30 : Quantos dias de dados manter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove dados antigos de sensores e logs operacionais para manter o banco saudável.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $days = (int) $this->option('days');
        $date = now()->subDays($days);

        $sensorCount = IoTSensorData::where('measured_at', '<', $date)->delete();
        $logCount = IoTDeviceLog::where('created_at', '<', $date)->delete();

        $this->info("Limpeza concluída: {$sensorCount} telemetrias e {$logCount} logs removidos.");
    }
}
